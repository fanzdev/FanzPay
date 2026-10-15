<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\MidtransClient;
use App\Providers\MockProvider;

/**
 * Deposit via Midtrans Core API (QRIS, Virtual Account, e-wallet).
 * Backend = sumber kebenaran. Saldo hanya dikredit setelah verifikasi sah.
 * Nominal integer rupiah. Fee ditanggung pelanggan, transparan sebelum bayar.
 */
final class MidtransService {
    public const METHODS = [
        'qris' => ['label' => 'QRIS', 'kind' => 'qris', 'icon' => 'qr'],
        'bca' => ['label' => 'BCA Virtual Account', 'kind' => 'va', 'icon' => 'bank'],
        'bri' => ['label' => 'BRI Virtual Account', 'kind' => 'va', 'icon' => 'bank'],
        'bni' => ['label' => 'BNI Virtual Account', 'kind' => 'va', 'icon' => 'bank'],
        'mandiri' => ['label' => 'Mandiri Bill Payment', 'kind' => 'va', 'icon' => 'bank'],
        'permata' => ['label' => 'Permata Virtual Account', 'kind' => 'va', 'icon' => 'bank'],
        'gopay' => ['label' => 'GoPay', 'kind' => 'ewallet', 'icon' => 'wallet'],
        'shopeepay' => ['label' => 'ShopeePay', 'kind' => 'ewallet', 'icon' => 'wallet'],
    ];

    public static function enabled(): bool {
        return Settings::get('deposit_enabled', '1') === '1';
    }

    /** @return array<string,array> */
    public static function enabledMethods(): array {
        $raw = strtolower((string)Settings::get('deposit_methods', 'qris,bca,bri,mandiri,permata,bni,gopay,shopeepay'));
        $list = array_filter(array_map('trim', explode(',', $raw)));
        $out = [];
        foreach ($list as $m) {
            if (isset(self::METHODS[$m])) $out[$m] = self::METHODS[$m];
        }
        return $out !== [] ? $out : ['qris' => self::METHODS['qris']];
    }

    public static function expiryMinutes(): int {
        $v = (int)Settings::get('deposit_expiry_minutes', '15');
        return min(60, max(5, $v === 0 ? 15 : $v));
    }

    /** Fee transaksi per metode (ditanggung pelanggan). */
    public static function feeFor(string $method, int $amount): int {
        switch ($method) {
            case 'qris':
                $pct = (float)Settings::get('midtrans_fee_qris_pct', '0.7');
                return (int)ceil($amount * max(0, $pct) / 100);
            case 'gopay':
                $pct = (float)Settings::get('midtrans_fee_gopay_pct', '2');
                return (int)ceil($amount * max(0, $pct) / 100);
            case 'shopeepay':
                $pct = (float)Settings::get('midtrans_fee_shopeepay_pct', '2');
                return (int)ceil($amount * max(0, $pct) / 100);
            case 'bca': case 'bri': case 'bni': case 'mandiri': case 'permata':
                return max(0, (int)Settings::get('midtrans_fee_' . $method, '4000'));
            default:
                return 0;
        }
    }

    /** @return array{amount:int, fee:int, total:int} */
    public static function quote(int $amount, string $method): array {
        $fee = self::feeFor($method, $amount);
        return ['amount' => $amount, 'fee' => $fee, 'total' => $amount + $fee];
    }

    /** @return array{ok:bool, message?:string, deposit?:array} */
    public static function create(int $userId, int $amount, string $method, string $idempotencyKey = '', array $customer = []): array {
        if (!self::enabled()) return ['ok' => false, 'message' => 'Fitur deposit sedang nonaktif.'];
        $method = strtolower(trim($method));
        if (!isset(self::METHODS[$method])) return ['ok' => false, 'message' => 'Metode pembayaran tidak didukung.'];
        if (!isset(self::enabledMethods()[$method])) return ['ok' => false, 'message' => 'Metode ' . self::METHODS[$method]['label'] . ' sedang nonaktif.'];

        $min = Settings::int('min_deposit', 10000);
        $max = Settings::int('max_deposit', 5000000);
        if ($amount < $min) return ['ok' => false, 'message' => 'Minimal deposit ' . rupiah($min) . '.'];
        if ($amount > $max) return ['ok' => false, 'message' => 'Maksimal deposit ' . rupiah($max) . '.'];

        if ($idempotencyKey !== '') {
            $dup = DB::one('SELECT response FROM idempotency_keys WHERE `key` = ? AND user_id = ?', [$idempotencyKey, $userId]);
            if ($dup && !empty($dup['response'])) {
                $prev = json_decode((string)$dup['response'], true);
                if (is_array($prev) && isset($prev['reference_id'])) {
                    $dep = DB::one('SELECT * FROM deposits WHERE reference_id = ?', [$prev['reference_id']]);
                    if ($dep) return ['ok' => true, 'deposit' => $dep];
                }
            }
        }

        $q = self::quote($amount, $method);
        $ref = new_reference('DEP');
        $expMin = self::expiryMinutes();
        $expiredAt = date('Y-m-d H:i:s', time() + $expMin * 60);

        // 1) Catat shell pending dulu — order_id sudah pasti unik milik kita.
        DB::tx(function () use ($userId, $ref, $q, $method, $expiredAt, $idempotencyKey) {
            $pdo = DB::pdo();
            $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note) VALUES (?,?,?,?,?,?, 'pending',?,?)")->execute([
                $ref, $userId, 'deposit', $q['amount'], $q['fee'], $q['total'],
                $idempotencyKey !== '' ? $idempotencyKey : null, 'Deposit via ' . self::METHODS[$method]['label'],
            ]);
            $txId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO deposits (transaction_id, user_id, reference_id, gateway, payment_type, amount, total_amount, fee, credit_amount, status, expired_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([
                $txId, $userId, $ref, 'midtrans', $method, $q['amount'], $q['total'], $q['fee'], $q['amount'], 'pending', $expiredAt,
            ]);
            if ($idempotencyKey !== '') {
                $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $userId, json_encode(['reference_id' => $ref])]);
            }
        });

        $client = new MidtransClient();
        if (!$client->configured()) {
            // Sandbox tanpa kredensial: instruksi mock agar UI 100% bisa diuji.
            $mock = self::mockCharge($method, $ref, $q['total'], $expiredAt);
            DB::exec('UPDATE deposits SET provider_txn_id=?, payment_code=?, qr_string=?, actions=?, qr_url=?, payment_url=?, is_mock=1 WHERE reference_id=?', [
                $mock['transaction_id'], $mock['payment_code'], $mock['qr_string'], json_encode($mock['actions'], JSON_UNESCAPED_UNICODE),
                $mock['qr_url'], $mock['payment_url'], $ref,
            ]);
            Logger::audit($userId, null, 'deposit.create.mock', 'deposits', $ref, 'ok', ['method' => $method]);
            return ['ok' => true, 'deposit' => DB::one('SELECT * FROM deposits WHERE reference_id = ?', [$ref])];
        }

        $payload = self::buildPayload($method, $ref, $q['total'], $expMin, $customer);
        $api = $client->charge($payload);
        if (!$api['ok']) {
            $unknown = stripos((string)($api['message'] ?? ''), 'timeout') !== false
                || stripos((string)($api['message'] ?? ''), 'sementara') !== false
                || stripos((string)($api['message'] ?? ''), 'UNKNOWN') !== false;
            if (!$unknown) {
                // Penolakan eksplisit (validasi/otorisasi) → tandai gagal, boleh coba lagi dgn ref baru.
                DB::exec("UPDATE deposits SET status='failed' WHERE reference_id=? AND status='pending'", [$ref]);
                DB::exec("UPDATE transactions SET status='failed' WHERE reference_id=?", [$ref]);
            } else {
                Logger::log('midtrans.charge-unknown', ['order' => $ref]);
            }
            return ['ok' => false, 'message' => (string)($api['message'] ?? 'Gagal membuat transaksi.')];
        }

        $d = $api['data'];
        $parsed = self::parseChargeResponse($method, $d);
        DB::exec('UPDATE deposits SET provider_txn_id=?, payment_code=?, qr_string=?, actions=?, qr_url=?, payment_url=?, raw_response=?, expired_at=? WHERE reference_id=?', [
            $d['transaction_id'] ?? null, $parsed['payment_code'], $parsed['qr_string'],
            json_encode($d['actions'] ?? [], JSON_UNESCAPED_UNICODE), $parsed['qr_url'], $parsed['payment_url'],
            json_encode($d, JSON_UNESCAPED_UNICODE), $parsed['expired_at'] ?? $expiredAt, $ref,
        ]);
        DB::exec('UPDATE transactions SET provider_ref=? WHERE reference_id=?', [$d['transaction_id'] ?? null, $ref]);
        Logger::audit($userId, null, 'deposit.create', 'deposits', $ref, 'ok', ['method' => $method]);
        return ['ok' => true, 'deposit' => DB::one('SELECT * FROM deposits WHERE reference_id = ?', [$ref])];
    }

    /** Bangun payload Core API /v2/charge sesuai metode. */
    public static function buildPayload(string $method, string $orderId, int $grossAmount, int $expiryMin, array $customer = []): array {
        $base = [
            'payment_type' => $method === 'mandiri' ? 'echannel' : ($method === 'qris' ? 'qris' : (in_array($method, ['gopay', 'shopeepay'], true) ? $method : 'bank_transfer')),
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $grossAmount],
            'custom_expiry' => [
                'order_time' => date('Y-m-d H:i:s O'),
                'expiry_duration' => $expiryMin,
                'unit' => 'minute',
            ],
        ];
        if (!empty($customer)) $base['customer_details'] = $customer;
        switch ($method) {
            case 'qris': $base['qris'] = ['acquirer' => 'gopay']; break;
            case 'bca': case 'bri': case 'bni': case 'permata':
                $base['bank_transfer'] = ['bank' => $method];
                break;
            case 'mandiri':
                $base['echannel'] = ['bill_info1' => 'Payment:', 'bill_info2' => 'FanzPay ' . $orderId];
                break;
            case 'gopay':
                $base['gopay'] = ['enable_callback' => true];
                if (!empty($customer['phone'])) $base['gopay']['callback_url'] = null;
                unset($base['gopay']['callback_url']);
                break;
            case 'shopeepay': $base['shopeepay'] = new \stdClass(); break;
        }
        return $base;
    }

    /** Ekstrak kode bayar/QR dari respons charge per metode. */
    public static function parseChargeResponse(string $method, array $d): array {
        $out = ['payment_code' => null, 'qr_string' => null, 'qr_url' => null, 'payment_url' => null, 'expired_at' => null];
        if (!empty($d['expiry_time'])) {
            $t = strtotime((string)$d['expiry_time']);
            if ($t) $out['expired_at'] = date('Y-m-d H:i:s', $t);
        }
        $actions = $d['actions'] ?? [];
        if (is_array($actions)) {
            foreach ($actions as $a) {
                if (($a['name'] ?? '') === 'generate-qr-code') $out['qr_url'] = $a['url'] ?? null;
                if (($a['name'] ?? '') === 'deeplink-redirect') $out['payment_url'] = $a['url'] ?? null;
            }
        }
        if ($method === 'qris') {
            $out['qr_string'] = $d['qr_string'] ?? null;
        } elseif (in_array($method, ['bca', 'bri', 'bni', 'permata'], true)) {
            foreach ((array)($d['va_numbers'] ?? []) as $va) {
                if (($va['bank'] ?? '') === $method && !empty($va['va_number'])) { $out['payment_code'] = $va['va_number']; break; }
            }
            if ($out['payment_code'] === null && isset($d['va_numbers'][0]['va_number'])) $out['payment_code'] = (string)$d['va_numbers'][0]['va_number'];
            if (!empty($d['biller_code']) && !empty($d['bill_key'])) $out['payment_code'] = (string)$d['biller_code'] . ' | ' . (string)$d['bill_key'];
        } elseif ($method === 'mandiri') {
            if (!empty($d['bill_key'])) $out['payment_code'] = (string)($d['biller_code'] ?? '') . ' | ' . (string)$d['bill_key'];
        } elseif (in_array($method, ['gopay', 'shopeepay'], true)) {
            $out['qr_string'] = $d['qr_string'] ?? null;
        }
        return $out;
    }

    /**
     * Terapkan notifikasi Midtrans (signature SUDAH lolos di controller).
     * Idempoten: webhook_events + guard status pending + unique ledger.
     * @return array{ok:bool, applied:bool, reason?:string}
     */
    public static function applyNotification(array $payload): array {
        $orderId = (string)($payload['order_id'] ?? '');
        $txnStatus = (string)($payload['transaction_status'] ?? '');
        $gross = (string)($payload['gross_amount'] ?? '');
        if ($orderId === '' || $txnStatus === '') return ['ok' => false, 'applied' => false, 'reason' => 'payload-tak-lengkap'];

        try {
            DB::exec(
                "INSERT INTO webhook_events (provider, event, delivery_id, payload, status) VALUES ('midtrans',?,?,?,'received') ON DUPLICATE KEY UPDATE payload=VALUES(payload)",
                [$txnStatus, $orderId, json_encode($payload, JSON_UNESCAPED_UNICODE)]
            );
        } catch (\Throwable $e) { Logger::log('webhook.db', ['provider' => 'midtrans']); }

        $seen = DB::one("SELECT status FROM webhook_events WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
        if ($seen && $seen['status'] === 'processed') return ['ok' => true, 'applied' => false, 'reason' => 'duplikat'];

        $dep = DB::one('SELECT * FROM deposits WHERE reference_id=?', [$orderId]);
        if (!$dep) {
            DB::exec("UPDATE webhook_events SET status='ignored' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
            Logger::log('midtrans.unknown-order', ['order' => $orderId]);
            return ['ok' => true, 'applied' => false, 'reason' => 'order-tak-dikenal'];
        }
        // Nominal wajib cocok dengan total tersimpan (anti manipulasi).
        if ((int)$gross !== (int)$dep['total_amount']) {
            DB::exec("UPDATE webhook_events SET status='ignored' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
            Logger::log('midtrans.amount-mismatch', ['order' => $orderId]);
            try {
                DB::exec("INSERT INTO security_events (user_id, kind, ip, detail) VALUES (?,?,?,?)", [(int)$dep['user_id'], 'midtrans_amount_mismatch', client_ip(), substr($orderId, 0, 60)]);
            } catch (\Throwable $e) {}
            return ['ok' => true, 'applied' => false, 'reason' => 'nominal-tak-cocok'];
        }

        if ($dep['status'] !== 'pending') {
            DB::exec("UPDATE webhook_events SET status='processed' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
            return ['ok' => true, 'applied' => false, 'reason' => 'sudah-diproses'];
        }

        $payType = (string)($payload['payment_type'] ?? '');
        if ($payType !== '' && $payType !== 'qris' && stripos((string)$dep['payment_type'], $payType) === false && stripos($payType, (string)$dep['payment_type']) === false) {
            Logger::log('midtrans.paytype-note', ['order' => $orderId, 'stored' => $dep['payment_type'], 'got' => $payType]);
        }

        if (in_array($txnStatus, ['settlement', 'capture'], true)) {
            if ($txnStatus === 'capture' && ($payload['fraud_status'] ?? 'accept') !== 'accept') {
                Logger::log('midtrans.capture-challenge', ['order' => $orderId]);
                return ['ok' => true, 'applied' => false, 'reason' => 'capture-challenge'];
            }
            return self::creditDeposit($dep, (string)($payload['transaction_id'] ?? ''), $orderId, $txnStatus, $payload);
        }
        if ($txnStatus === 'pending') {
            DB::exec("UPDATE webhook_events SET status='processed' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
            return ['ok' => true, 'applied' => false, 'reason' => 'pending'];
        }
        if (in_array($txnStatus, ['deny', 'cancel', 'failure'], true)) {
            self::markTerminal($dep, 'failed', $orderId, $txnStatus);
            return ['ok' => true, 'applied' => true, 'reason' => $txnStatus];
        }
        if ($txnStatus === 'expire') {
            self::markTerminal($dep, 'expired', $orderId, $txnStatus);
            return ['ok' => true, 'applied' => true, 'reason' => 'expire'];
        }
        DB::exec("UPDATE webhook_events SET status='ignored' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $txnStatus]);
        return ['ok' => true, 'applied' => false, 'reason' => 'status-tak-dikenal'];
    }

    /** Kredit saldo satu-kali: guard status pending di dalam DB transaction + FOR UPDATE. */
    private static function creditDeposit(array $dep, string $midTxnId, string $orderId, string $event, array $payload): array {
        try {
            DB::tx(function () use ($dep, $midTxnId, $orderId, $event, $payload) {
                $pdo = DB::pdo();
                $lock = $pdo->prepare('SELECT status FROM deposits WHERE id=? FOR UPDATE');
                $lock->execute([(int)$dep['id']]);
                $row = $lock->fetch();
                if (!$row || $row['status'] !== 'pending') return; // balapan dimenangkan proses lain
                $credit = (int)$dep['credit_amount'];
                $pdo->prepare("UPDATE deposits SET status='success', paid_at=NOW(), credited_at=NOW(), provider_txn_id=COALESCE(provider_txn_id,?) WHERE id=? AND status='pending'")->execute([$midTxnId !== '' ? $midTxnId : $dep['provider_txn_id'], (int)$dep['id']]);
                $pdo->prepare("UPDATE transactions SET status='success', provider_ref=COALESCE(provider_ref,?) WHERE id=?")->execute([$midTxnId !== '' ? $midTxnId : null, (int)$dep['transaction_id']]);
                WalletService::append((int)$dep['user_id'], 'DEP-' . $dep['reference_id'], (string)$dep['reference_id'], 'deposit', 'credit', $credit, [
                    'source' => 'midtrans',
                    'provider_txn_id' => $midTxnId !== '' ? $midTxnId : $dep['provider_txn_id'],
                    'metadata' => ['payment_type' => $dep['payment_type'], 'total_amount' => $dep['total_amount']],
                ]);
                $pdo->prepare("UPDATE webhook_events SET status='processed' WHERE provider='midtrans' AND delivery_id=? AND event=?")->execute([$orderId, $event]);
            });
        } catch (\Throwable $e) {
            // Unique violation ledger = duplikat kredit yang berhasil dicegah.
            Logger::log('midtrans.credit-guarded', ['order' => $orderId, 'msg' => substr($e->getMessage(), 0, 120)]);
            return ['ok' => true, 'applied' => false, 'reason' => 'guarded'];
        }
        NotificationService::send((int)$dep['user_id'], 'deposit', 'Deposit berhasil', 'Saldo ' . rupiah((int)$dep['credit_amount']) . ' telah masuk.');
        Logger::audit((int)$dep['user_id'], null, 'deposit.success', 'deposits', $dep['reference_id'], 'ok', ['via' => 'midtrans']);
        return ['ok' => true, 'applied' => true, 'reason' => 'settlement'];
    }

    private static function markTerminal(array $dep, string $to, string $orderId, string $event): void {
        DB::exec('UPDATE deposits SET status=? WHERE id=? AND status=?', [$to, (int)$dep['id'], 'pending']);
        DB::exec('UPDATE transactions SET status=? WHERE id=?', [$to, (int)$dep['transaction_id']]);
        DB::exec("UPDATE webhook_events SET status='processed' WHERE provider='midtrans' AND delivery_id=? AND event=?", [$orderId, $event]);
        $label = $to === 'expired' ? 'kedaluwarsa' : 'gagal';
        NotificationService::send((int)$dep['user_id'], 'deposit', 'Deposit ' . $label, 'Deposit ' . $dep['reference_id'] . ' ' . $label . '.');
        Logger::audit((int)$dep['user_id'], null, 'deposit.' . $to, 'deposits', $dep['reference_id']);
    }

    /**
     * Rekonsiliasi: sinkronkan deposit pending via Status API resmi.
     * Tidak pernah kredit ulang otomatis tanpa status sah — hanya settlement/capture yang dikredit.
     * @return array{checked:int, credited:int, expired:int, failed:int}
     */
    public static function syncPending(int $limit = 20): array {
        $out = ['checked' => 0, 'credited' => 0, 'expired' => 0, 'failed' => 0];
        $client = new MidtransClient();
        if (!$client->configured()) return $out;
        $rows = DB::all("SELECT * FROM deposits WHERE gateway='midtrans' AND status='pending' AND COALESCE(is_mock,0)=0 ORDER BY id LIMIT ?", [$limit]);
        foreach ($rows as $dep) {
            $out['checked']++;
            $st = $client->status((string)$dep['reference_id']);
            if (!$st['ok']) {
                if (($st['http'] ?? 0) === 404 && strtotime((string)$dep['created_at']) < time() - 1800) {
                    self::markTerminal($dep, 'expired', (string)$dep['reference_id'], 'reconcile-404');
                    $out['expired']++;
                }
                continue;
            }
            $d = $st['data'];
            $ts = (string)($d['transaction_status'] ?? '');
            if (in_array($ts, ['settlement', 'capture'], true)) {
                if ($ts === 'capture' && ($d['fraud_status'] ?? 'accept') !== 'accept') continue;
                if ((int)($d['gross_amount'] ?? -1) !== (int)$dep['total_amount']) {
                    Logger::log('midtrans.reconcile-mismatch', ['order' => $dep['reference_id']]);
                    continue;
                }
                $r = self::creditDeposit($dep, (string)($d['transaction_id'] ?? ''), (string)$dep['reference_id'], $ts, $d);
                if ($r['applied']) $out['credited']++;
            } elseif ($ts === 'expire') {
                self::markTerminal($dep, 'expired', (string)$dep['reference_id'], $ts);
                $out['expired']++;
            } elseif (in_array($ts, ['deny', 'cancel', 'failure'], true)) {
                self::markTerminal($dep, 'failed', (string)$dep['reference_id'], $ts);
                $out['failed']++;
            }
        }
        return $out;
    }

    /** Tandai deposit lokal yang lewat masa berlaku (dipanggil cron + saat halaman dibuka). */
    public static function expireLocal(int $limit = 100): int {
        $rows = DB::all("SELECT id, transaction_id, reference_id, provider_txn_id FROM deposits WHERE status='pending' AND expired_at IS NOT NULL AND expired_at < NOW() LIMIT ?", [$limit]);
        $n = 0;
        $client = null;
        foreach ($rows as $r) {
            // Best effort: minta Midtrans ikut kedaluwarsakan (abaikan gagal).
            try {
                if ($client === null) $client = new MidtransClient();
                if ($client->configured() && empty($r['provider_txn_id']) === false) { /* expire by order_id */ @$client->expire((string)$r['reference_id']); }
            } catch (\Throwable $e) {}
            DB::exec("UPDATE deposits SET status='expired' WHERE id=? AND status='pending'", [(int)$r['id']]);
            DB::exec("UPDATE transactions SET status='expired' WHERE id=?", [(int)$r['transaction_id']]);
            $n++;
        }
        return $n;
    }

    /** Simulasi sukses untuk deposit MOCK (sandbox tanpa kredensial). Super-admin only (dicek di controller). */
    public static function simulateSuccess(string $referenceId): array {
        $dep = DB::one('SELECT * FROM deposits WHERE reference_id=?', [$referenceId]);
        if (!$dep) return ['ok' => false, 'message' => 'Deposit tidak ditemukan.'];
        if (empty($dep['is_mock'])) return ['ok' => false, 'message' => 'Hanya deposit sandbox/mock yang bisa disimulasikan.'];
        if ($dep['status'] !== 'pending') return ['ok' => false, 'message' => 'Deposit sudah ' . $dep['status'] . '.'];
        $r = self::creditDeposit($dep, (string)($dep['provider_txn_id'] ?? ''), $referenceId, 'settlement', ['simulated' => true]);
        return $r['applied'] ? ['ok' => true] : ['ok' => false, 'message' => 'Gagal memproses (mungkin sudah diproses).'];
    }

    private static function mockCharge(string $method, string $ref, int $total, string $expiredAt): array {
        $bank = strtoupper($method);
        $va = match ($method) {
            'bca' => '39008' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'bri' => '88000' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'bni' => '88100' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'permata' => '87780' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'mandiri' => '88900' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
            default => null,
        };
        $actions = [];
        $qrUrl = null; $payUrl = null; $qrStr = null;
        if ($method === 'qris') {
            $qrStr = 'MOCK-QRIS-' . $ref . '-' . $total;
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($qrStr);
        } elseif (in_array($method, ['gopay', 'shopeepay'], true)) {
            $qrStr = 'MOCK-' . $bank . '-' . $ref;
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($qrStr);
            $payUrl = '#mock-' . $method;
            $actions = [['name' => 'deeplink-redirect', 'method' => 'GET', 'url' => $payUrl]];
        }
        return [
            'transaction_id' => 'MOCK-MT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'payment_code' => $va, 'qr_string' => $qrStr, 'qr_url' => $qrUrl, 'payment_url' => $payUrl,
            'actions' => $actions,
        ];
    }
}
