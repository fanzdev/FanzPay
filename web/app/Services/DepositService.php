<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\BuatQrisClient;

/**
 * Deposit facade.
 * - Jalur utama: Midtrans Core API (QRIS, VA, e-wallet) via MidtransService.
 * - Jalur legacy: BuatQris QRIS (dipertahankan agar fitur lama tidak rusak).
 * Backend = sumber kebenaran. Frontend hanya menampilkan status.
 */
final class DepositService {
    /** @return array{ok:bool, message?:string, deposit?:array} */
    public static function create(int $userId, int $amount, string $idempotencyKey = '', string $method = ''): array {
        $gateway = Settings::get('deposit_gateway', 'midtrans');
        if ($gateway === 'midtrans' || $method !== '') {
            $customer = [];
            try {
                $u = DB::one('SELECT name, email, phone FROM users WHERE id=?', [$userId]);
                if ($u) {
                    $names = explode(' ', trim((string)$u['name']));
                    $customer = ['first_name' => $names[0] ?? 'Pelanggan', 'email' => (string)$u['email'], 'phone' => (string)$u['phone']];
                }
            } catch (\Throwable $e) {}
            return MidtransService::create($userId, $amount, $method !== '' ? $method : 'qris', $idempotencyKey, $customer);
        }
        return self::createLegacyBuatQris($userId, $amount, $idempotencyKey);
    }

    /** Jalur lama BuatQris — dipertahankan untuk kompatibilitas. */
    public static function createLegacyBuatQris(int $userId, int $amount, string $idempotencyKey = ''): array {
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

        $client = new BuatQrisClient();
        $ref = new_reference('DEP');
        $api = $client->createQris($amount, 'Deposit FanzPay ' . $ref);
        if (!$api['ok']) return ['ok' => false, 'message' => (string)($api['message'] ?? 'Gagal membuat QRIS.')];
        $d = $api['data'];

        $isMock = !empty($d['is_mock']) ? 1 : 0;
        $dep = DB::tx(function () use ($userId, $amount, $ref, $d, $idempotencyKey, $isMock) {
            $pdo = DB::pdo();
            $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note, provider_ref) VALUES (?,?,?,?,?,?, 'pending',?,?,?)")->execute([
                $ref, $userId, 'deposit', $amount, (int)($d['admin_fee'] ?? 0), (int)($d['total_amount'] ?? $amount), $idempotencyKey !== '' ? $idempotencyKey : null, 'Deposit via QRIS', $d['transaction_id'] ?? null,
            ]);
            $txId = (int)$pdo->lastInsertId();
            $cols = self::depositColumns();
            $gw = $cols['gateway'] ? ', gateway' : '';
            $gv = $cols['gateway'] ? ", 'buatqris'" : '';
            $pt = $cols['payment_type'] ? ', payment_type' : '';
            $pv = $cols['payment_type'] ? ", 'qris'" : '';
            $pdo->prepare("INSERT INTO deposits (transaction_id, user_id, reference_id, provider_txn_id, amount, total_amount, fee, credit_amount, status, qr_url, payment_url, expired_at, is_mock{$gw}{$pt}) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?{$gv}{$pv})")->execute([
                $txId, $userId, $ref, $d['transaction_id'] ?? null, $amount, (int)($d['total_amount'] ?? $amount),
                (int)($d['admin_fee'] ?? 0), (int)($d['credit_amount'] ?? $amount), 'pending',
                $d['qr_url'] ?? null, $d['payment_url'] ?? null, self::normDate($d['expired_at'] ?? null), $isMock,
            ]);
            if ($idempotencyKey !== '') {
                $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $userId, json_encode(['reference_id' => $ref])]);
            }
            return DB::one('SELECT * FROM deposits WHERE reference_id = ?', [$ref]);
        });

        Logger::audit($userId, null, 'deposit.create', 'deposits', $ref);
        return ['ok' => true, 'deposit' => $dep];
    }

    private static function depositColumns(): array {
        static $c = null;
        if ($c !== null) return $c;
        $c = ['gateway' => false, 'payment_type' => false];
        try {
            foreach (DB::all('SHOW COLUMNS FROM deposits') as $row) {
                if (($row['Field'] ?? '') === 'gateway') $c['gateway'] = true;
                if (($row['Field'] ?? '') === 'payment_type') $c['payment_type'] = true;
            }
        } catch (\Throwable $e) {}
        return $c;
    }

    /**
     * Terapkan webhook payment.* BuatQris — IDEMPOTEN. Dipanggil setelah signature lolos.
     * @return array{ok:bool, applied:bool}
     */
    public static function applyWebhook(array $payload): array {
        $event = (string)($payload['event'] ?? '');
        $txnId = (string)($payload['transaction_id'] ?? '');
        if ($txnId === '') return ['ok' => false, 'applied' => false];
        try {
            $seen = DB::one('SELECT status FROM webhook_events WHERE provider = ? AND delivery_id = ? AND event = ?', ['buatqris', $txnId, $event]);
            if ($seen && $seen['status'] === 'processed') return ['ok' => true, 'applied' => false];
            DB::exec("INSERT INTO webhook_events (provider, event, delivery_id, payload, status) VALUES (?,?,?,?, 'received') ON DUPLICATE KEY UPDATE payload=VALUES(payload)", ['buatqris', $event, $txnId, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) {
            Logger::log('webhook.db', ['event' => $event]);
        }

        $dep = DB::one('SELECT * FROM deposits WHERE provider_txn_id = ?', [$txnId]);
        if (!$dep) {
            DB::exec("UPDATE webhook_events SET status='ignored' WHERE provider='buatqris' AND delivery_id=? AND event=?", [$txnId, $event]);
            return ['ok' => true, 'applied' => false];
        }
        if ($dep['status'] !== 'pending') {
            DB::exec("UPDATE webhook_events SET status='processed' WHERE provider='buatqris' AND delivery_id=? AND event=?", [$txnId, $event]);
            return ['ok' => true, 'applied' => false]; // retry duplikat: abaikan
        }

        if ($event === 'payment.success') {
            DB::tx(function () use ($dep, $payload, $txnId, $event) {
                $pdo = DB::pdo();
                $credit = (int)$dep['credit_amount'];
                $pdo->prepare("UPDATE deposits SET status='success', paid_at=NOW() WHERE id=? AND status='pending'")->execute([(int)$dep['id']]);
                $pdo->prepare("UPDATE transactions SET status='success', provider_ref=? WHERE id=?")->execute([$txnId, (int)$dep['transaction_id']]);
                $txnRef = 'DEP-' . $dep['reference_id'];
                WalletService::append((int)$dep['user_id'], $txnRef, (string)$dep['reference_id'], 'deposit', 'credit', $credit, ['source' => 'buatqris', 'provider_txn_id' => $txnId, 'metadata' => ['total_amount' => $payload['total_amount'] ?? null]]);
                $pdo->prepare("UPDATE webhook_events SET status='processed' WHERE provider='buatqris' AND delivery_id=? AND event=?")->execute([$txnId, $event]);
            });
            NotificationService::send((int)$dep['user_id'], 'deposit', 'Deposit berhasil', 'Saldo ' . rupiah((int)$dep['credit_amount']) . ' telah masuk.');
            Logger::audit((int)$dep['user_id'], null, 'deposit.success', 'deposits', $dep['reference_id']);
            return ['ok' => true, 'applied' => true];
        }

        if (in_array($event, ['payment.expired', 'payment.failed'], true)) {
            $st = $event === 'payment.expired' ? 'expired' : 'failed';
            DB::exec('UPDATE deposits SET status = ? WHERE id = ? AND status = ?', [$st, (int)$dep['id'], 'pending']);
            DB::exec('UPDATE transactions SET status = ? WHERE id = ?', [$st, (int)$dep['transaction_id']]);
            DB::exec("UPDATE webhook_events SET status='processed' WHERE provider='buatqris' AND delivery_id=? AND event=?", [$txnId, $event]);
            NotificationService::send((int)$dep['user_id'], 'deposit', 'Deposit ' . $st, 'QRIS ' . $dep['reference_id'] . ' ' . $st . '.');
            return ['ok' => true, 'applied' => true];
        }
        return ['ok' => true, 'applied' => false];
    }

    private static function normDate($v): ?string {
        if (!$v) return null;
        $t = strtotime((string)$v);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }
}
