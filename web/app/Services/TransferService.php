<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Core\Validator;

/**
 * Transfer GRATIS antar user (Rp0). Atomic: debit+credit dalam 1 transaksi.
 * Double-submit aman via idempotency key + unique reference.
 */
final class TransferService {
    /** @return array{ok:bool, message?:string, transfer?:array} */
    public static function transfer(int $senderId, string $lookup, int $amount, string $pin, string $idempotencyKey = ''): array {
        $min = Settings::int('min_transfer', 1000);
        $max = Settings::int('max_transfer', 10000000);
        if ($amount < $min) return ['ok' => false, 'message' => 'Minimal transfer ' . rupiah($min) . '.'];
        if ($amount > $max) return ['ok' => false, 'message' => 'Maksimal transfer ' . rupiah($max) . '.'];

        $pinCheck = PinService::verify($senderId, $pin);
        if (!$pinCheck['ok']) return $pinCheck;

        $lookup = trim($lookup);
        $phone = Validator::normalizePhone(preg_replace('/^fanzpay:\/\/pay\/u\//i', '', $lookup));
        $recipient = DB::one('SELECT * FROM users WHERE (phone = ? OR public_id = ?) AND role = ? AND status = ?', [$phone, strtoupper($lookup) === $lookup && str_starts_with(strtoupper($lookup), 'FP') ? $lookup : $phone, 'user', 'active']);
        if (!$recipient && str_starts_with(strtolower($lookup), 'fanzpay://pay/u/')) {
            $pid = substr($lookup, strlen('fanzpay://pay/u/'));
            $recipient = DB::one('SELECT * FROM users WHERE public_id = ? AND status = ?', [$pid, 'active']);
        }
        if (!$recipient) return ['ok' => false, 'message' => 'Penerima tidak ditemukan.'];
        if ((int)$recipient['id'] === $senderId) return ['ok' => false, 'message' => 'Tidak bisa transfer ke diri sendiri.'];

        if ($idempotencyKey !== '') {
            $dup = DB::one('SELECT response FROM idempotency_keys WHERE `key` = ? AND user_id = ?', [$idempotencyKey, $senderId]);
            if ($dup && !empty($dup['response'])) {
                $prev = json_decode((string)$dup['response'], true);
                if (is_array($prev) && isset($prev['reference_id'])) {
                    $t = DB::one('SELECT * FROM internal_transfers WHERE reference_id = ?', [$prev['reference_id']]);
                    if ($t) return ['ok' => true, 'transfer' => $t];
                }
            }
        }

        $ref = new_reference('TRF');
        try {
            $out = DB::tx(function () use ($senderId, $recipient, $amount, $ref, $idempotencyKey) {
                $pdo = DB::pdo();
                // Lock kedua wallet berurutan by id → cegah deadlock
                $ids = [$senderId, (int)$recipient['id']];
                sort($ids);
                foreach ($ids as $uid) {
                    $pdo->prepare('SELECT user_id FROM wallets WHERE user_id = ? FOR UPDATE')->execute([$uid]);
                }
                $bal = WalletService::balance($senderId);
                if ($bal < $amount) throw new \RuntimeException('Saldo tidak mencukupi.');
                $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note) VALUES (?,?,?,?,0,?, 'success',?,?)")->execute([
                    $ref, $senderId, 'transfer', $amount, $amount, $idempotencyKey !== '' ? $idempotencyKey : null, 'Transfer ke ' . $recipient['name'],
                ]);
                $txId = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO internal_transfers (transaction_id, sender_id, recipient_id, reference_id, amount, status) VALUES (?,?,?,?,?,?)')->execute([$txId, $senderId, (int)$recipient['id'], $ref, $amount, 'success']);
                WalletService::append($senderId, 'TRF-OUT-' . $ref, $ref, 'transfer_out', 'debit', $amount, ['destination' => 'user:' . $recipient['id']]);
                WalletService::append((int)$recipient['id'], 'TRF-IN-' . $ref, $ref, 'transfer_in', 'credit', $amount, ['source' => 'user:' . $senderId]);
                if ($idempotencyKey !== '') {
                    $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $senderId, json_encode(['reference_id' => $ref])]);
                }
                return DB::one('SELECT * FROM internal_transfers WHERE reference_id = ?', [$ref]);
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        NotificationService::send($senderId, 'transfer', 'Transfer terkirim', rupiah($amount) . ' ke ' . $recipient['name'] . '. Gratis biaya.');
        NotificationService::send((int)$recipient['id'], 'transfer', 'Transfer diterima', rupiah($amount) . ' dari pengirim FanzPay.');
        Logger::audit($senderId, null, 'transfer.send', 'internal_transfers', $ref);
        return ['ok' => true, 'transfer' => $out];
    }
}
