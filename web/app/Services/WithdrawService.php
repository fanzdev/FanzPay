<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\BuatQrisClient;

/**
 * Withdrawal: lock saldo → request provider → webhook finalize.
 * Gagal/ditolak → reversal ledger (saldo kembali), TIDAK pernah hilang.
 */
final class WithdrawService {
    /** @return array{ok:bool, message?:string, withdrawal?:array} */
    public static function request(int $userId, int $destinationId, int $amount, string $pin, string $idempotencyKey = ''): array {
        $min = Settings::int('min_withdraw', 20000);
        $max = Settings::int('max_withdraw', 5000000);
        if ($amount < $min) return ['ok' => false, 'message' => 'Minimal penarikan ' . rupiah($min) . '.'];
        if ($amount > $max) return ['ok' => false, 'message' => 'Maksimal penarikan ' . rupiah($max) . '.'];

        $pinCheck = PinService::verify($userId, $pin);
        if (!$pinCheck['ok']) return $pinCheck;

        $dest = DB::one('SELECT * FROM withdrawal_destinations WHERE id = ? AND user_id = ?', [$destinationId, $userId]);
        if (!$dest) return ['ok' => false, 'message' => 'Tujuan penarikan tidak valid.'];

        if ($idempotencyKey !== '') {
            $dup = DB::one('SELECT response FROM idempotency_keys WHERE `key` = ? AND user_id = ?', [$idempotencyKey, $userId]);
            if ($dup && !empty($dup['response'])) {
                $prev = json_decode((string)$dup['response'], true);
                if (is_array($prev) && isset($prev['reference_id'])) {
                    $w = DB::one('SELECT * FROM withdrawals WHERE reference_id = ?', [$prev['reference_id']]);
                    if ($w) return ['ok' => true, 'withdrawal' => $w];
                }
            }
        }

        $ref = new_reference('WD');
        try {
            $out = DB::tx(function () use ($userId, $dest, $amount, $ref, $idempotencyKey) {
                $pdo = DB::pdo();
                $bal = WalletService::balance($userId);
                if ($bal < $amount) throw new \RuntimeException('Saldo tidak mencukupi.');
                $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note) VALUES (?,?,?,?,0,?, 'pending',?,?)")->execute([
                    $ref, $userId, 'withdraw', $amount, $amount, $idempotencyKey !== '' ? $idempotencyKey : null, 'Withdraw ke ' . $dest['bank_name'],
                ]);
                $txId = (int)$pdo->lastInsertId();
                WalletService::append($userId, 'WD-LOCK-' . $ref, $ref, 'withdraw', 'debit', $amount, ['destination' => 'withdraw-pending', 'status' => 'pending', 'metadata' => ['locked' => true]]);
                $pdo->prepare('INSERT INTO withdrawals (transaction_id, user_id, reference_id, destination_id, amount, fee, net_amount, status) VALUES (?,?,?,?,?,?,?,?)')->execute([
                    $txId, $userId, $ref, (int)$dest['id'], $amount, 0, $amount, 'pending',
                ]);
                if ($idempotencyKey !== '') {
                    $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $userId, json_encode(['reference_id' => $ref])]);
                }
                return DB::one('SELECT * FROM withdrawals WHERE reference_id = ?', [$ref]);
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        // Panggil provider (mock bila sandbox/belum konfigurasi)
        $mode = Settings::get('provider_mode', 'sandbox');
        $wdAccount = (string)Settings::get('buatqris_wd_account', '');
        $wdPinCfg = (string)Settings::get('buatqris_wd_pin', '');
        $provWdid = null; $fee = 0; $net = $amount;
        if ($mode !== 'manual') {
            $api = (new BuatQrisClient())->withdraw($amount, $wdAccount !== '' ? $wdAccount : 'sandbox', $wdPinCfg !== '' ? $wdPinCfg : '000000');
            if (!$api['ok']) {
                // Provider menolak di awal → kembalikan lock segera
                self::reverse($userId, $ref, 'Provider: ' . ($api['message'] ?? 'ditolak'));
                return ['ok' => false, 'message' => (string)($api['message'] ?? 'Penarikan ditolak provider. Saldo dikembalikan.')];
            }
            $provWdid = $api['data']['withdrawal_id'] ?? null;
            $fee = (int)($api['data']['fee'] ?? 0);
            $net = (int)($api['data']['net_amount'] ?? $amount);
            DB::exec('UPDATE withdrawals SET provider_withdrawal_id = ?, fee = ?, net_amount = ? WHERE reference_id = ?', [$provWdid, $fee, $net, $ref]);
            $out = DB::one('SELECT * FROM withdrawals WHERE reference_id = ?', [$ref]);
        }

        NotificationService::send($userId, 'withdraw', 'Penarikan diajukan', rupiah($amount) . ' sedang diproses.');
        Logger::audit($userId, null, 'withdraw.request', 'withdrawals', $ref);
        return ['ok' => true, 'withdrawal' => $out];
    }

    /** Webhook withdrawal.approved / rejected — idempoten. */
    public static function applyWebhook(array $payload): array {
        $event = (string)($payload['event'] ?? '');
        $wdid = (string)($payload['withdrawal_id'] ?? $payload['transaction_id'] ?? '');
        if ($wdid === '') return ['ok' => false, 'applied' => false];
        $w = DB::one('SELECT * FROM withdrawals WHERE provider_withdrawal_id = ?', [$wdid]);
        if (!$w || $w['status'] !== 'pending') return ['ok' => true, 'applied' => false];
        if ($event === 'withdrawal.approved') {
            DB::exec("UPDATE withdrawals SET status='approved', processed_at=NOW() WHERE id=? AND status='pending'", [(int)$w['id']]);
            DB::exec("UPDATE transactions SET status='success' WHERE id=?", [(int)$w['transaction_id']]);
            NotificationService::send((int)$w['user_id'], 'withdraw', 'Penarikan disetujui', rupiah((int)$w['net_amount']) . ' dicairkan.');
            Logger::audit((int)$w['user_id'], null, 'withdraw.approved', 'withdrawals', $w['reference_id']);
            return ['ok' => true, 'applied' => true];
        }
        if ($event === 'withdrawal.rejected') {
            self::reverse((int)$w['user_id'], (string)$w['reference_id'], 'Ditolak provider');
            NotificationService::send((int)$w['user_id'], 'withdraw', 'Penarikan ditolak', 'Saldo ' . rupiah((int)$w['amount']) . ' dikembalikan.');
            return ['ok' => true, 'applied' => true];
        }
        return ['ok' => true, 'applied' => false];
    }

    /** Kembalikan locked balance via reversal ledger. */
    public static function reverse(int $userId, string $referenceId, string $reason): void {
        DB::tx(function () use ($userId, $referenceId, $reason) {
            $pdo = DB::pdo();
            $w = DB::one('SELECT * FROM withdrawals WHERE reference_id = ? FOR UPDATE', [$referenceId]);
            if (!$w || $w['status'] !== 'pending') return;
            $pdo->prepare("UPDATE withdrawals SET status='rejected', note=? WHERE id=?")->execute([$reason, (int)$w['id']]);
            $pdo->prepare("UPDATE transactions SET status='reversed' WHERE id=?")->execute([(int)$w['transaction_id']]);
            WalletService::append($userId, 'WD-REV-' . $referenceId, $referenceId, 'refund', 'credit', (int)$w['amount'], ['source' => 'withdraw-reversal', 'metadata' => ['reason' => $reason]]);
        });
        Logger::audit($userId, null, 'withdraw.reversed', 'withdrawals', $referenceId);
    }
}
