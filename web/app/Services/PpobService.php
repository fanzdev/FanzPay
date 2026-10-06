<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\DigiFlazzClient;

/**
 * PPOB: validasi → harga jual → PIN → lock saldo → call DigiFlazz
 * → SUCCESS finalize / PENDING simpan / FAILED refund otomatis.
 */
final class PpobService {
    /** @return array{ok:bool, message?:string, transaction?:array} */
    public static function purchase(int $userId, string $productCode, string $customerNo, string $pin, string $idempotencyKey = ''): array {
        $customerNo = preg_replace('/\s+/', '', trim($customerNo));
        if ($customerNo === '' || strlen($customerNo) > 32) return ['ok' => false, 'message' => 'Nomor tujuan tidak valid.'];

        $pinCheck = PinService::verify($userId, $pin);
        if (!$pinCheck['ok']) return $pinCheck;

        $prod = DB::one('SELECT p.*, pp.sell_price, c.code cat_code FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id WHERE p.code=? AND p.is_active=1', [$productCode]);
        if (!$prod || empty($prod['sell_price'])) return ['ok' => false, 'message' => 'Produk tidak tersedia.'];
        $sell = (int)$prod['sell_price'];

        if ($idempotencyKey !== '') {
            $dup = DB::one('SELECT response FROM idempotency_keys WHERE `key` = ? AND user_id = ?', [$idempotencyKey, $userId]);
            if ($dup && !empty($dup['response'])) {
                $prev = json_decode((string)$dup['response'], true);
                if (is_array($prev) && isset($prev['reference_id'])) {
                    $t = DB::one('SELECT * FROM transactions WHERE reference_id = ?', [$prev['reference_id']]);
                    if ($t) return ['ok' => true, 'transaction' => $t];
                }
            }
        }

        $pp = DB::one('SELECT * FROM provider_products WHERE provider_sku = ? OR product_id = ? ORDER BY id DESC LIMIT 1', [$productCode, (int)$prod['id']]);
        $sku = $pp['provider_sku'] ?? $productCode;
        $base = (int)($pp['base_price'] ?? ($sell - 1500));

        $ref = new_reference('PPOB');
        try {
            DB::tx(function () use ($userId, $sell, $ref, $idempotencyKey, $prod, $customerNo, $sku, $base) {
                $pdo = DB::pdo();
                if (WalletService::balance($userId) < $sell) throw new \RuntimeException('Saldo tidak mencukupi.');
                $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note) VALUES (?,?, 'ppob', ?, 0, ?, 'processing',?,?)")->execute([
                    $ref, $userId, $sell, $sell, $idempotencyKey !== '' ? $idempotencyKey : null, $prod['name'] . ' / ' . $customerNo,
                ]);
                $txId = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO transaction_items (transaction_id, label, qty, unit_price, subtotal) VALUES (?,?,?,?,?)')->execute([$txId, $prod['name'] . ' — ' . $customerNo, 1, $sell, $sell]);
                $pdo->prepare('INSERT INTO provider_transactions (transaction_id, provider_id, provider_sku, customer_no, base_price, sell_price, status) VALUES (?, (SELECT id FROM providers WHERE code="digiflazz"), ?,?,?,?, ?)')->execute([$txId, $sku, $customerNo, $base, $sell, 'pending']);
                WalletService::append($userId, 'PPOB-LOCK-' . $ref, $ref, 'ppob', 'debit', $sell, ['destination' => 'digiflazz-pending', 'status' => 'pending']);
                if ($idempotencyKey !== '') {
                    $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $userId, json_encode(['reference_id' => $ref])]);
                }
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $api = (new DigiFlazzClient())->transaction($sku, $customerNo, $ref);
        if (!$api['ok']) {
            self::refund($userId, $ref, 'Provider tidak merespons');
            return ['ok' => false, 'message' => 'Transaksi gagal. Saldo dikembalikan.'];
        }
        $status = strtoupper((string)($api['data']['status'] ?? 'PENDING'));
        DB::exec('UPDATE provider_transactions SET response = ?, status = ?, provider_ref = ? WHERE transaction_id = (SELECT id FROM transactions WHERE reference_id = ?)', [
            json_encode($api['data'], JSON_UNESCAPED_UNICODE), strtolower($status), (string)($api['data']['sn'] ?? $api['data']['ref_id'] ?? ''), $ref,
        ]);

        if ($status === 'SUKSES') {
            DB::exec("UPDATE transactions SET status='success' WHERE reference_id=?", [$ref]);
            NotificationService::send($userId, 'ppob', 'Pembelian berhasil', $prod['name'] . ' ' . $customerNo);
        } elseif (in_array($status, ['GAGAL', 'FAILED'], true)) {
            self::refund($userId, $ref, 'Gagal dari provider');
            return ['ok' => false, 'message' => 'Transaksi gagal dari provider. Saldo dikembalikan.'];
        } else {
            DB::exec("UPDATE transactions SET status='pending' WHERE reference_id=?", [$ref]);
            NotificationService::send($userId, 'ppob', 'Transaksi diproses', $prod['name'] . ' menunggu provider.');
        }
        Logger::audit($userId, null, 'ppob.purchase', 'transactions', $ref);
        $t = DB::one('SELECT * FROM transactions WHERE reference_id = ?', [$ref]);
        return ['ok' => true, 'transaction' => $t];
    }

    public static function refund(int $userId, string $referenceId, string $reason): void {
        DB::tx(function () use ($userId, $referenceId, $reason) {
            $pdo = DB::pdo();
            $t = DB::one('SELECT * FROM transactions WHERE reference_id = ? FOR UPDATE', [$referenceId]);
            if (!$t || in_array($t['status'], ['success', 'refunded'], true)) return;
            $pdo->prepare("UPDATE transactions SET status='refunded' WHERE id=?")->execute([(int)$t['id']]);
            WalletService::append($userId, 'PPOB-REF-' . $referenceId, $referenceId, 'refund', 'credit', (int)$t['total'], ['source' => 'ppob-refund', 'metadata' => ['reason' => $reason]]);
        });
        NotificationService::send($userId, 'ppob', 'Dana dikembalikan', rupiah((int)(DB::one('SELECT total FROM transactions WHERE reference_id=?', [$referenceId])['total'] ?? 0)) . ' — ' . $reason);
        Logger::audit($userId, null, 'ppob.refund', 'transactions', $referenceId);
    }
}
