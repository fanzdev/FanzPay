<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\DigiFlazzClient;

/**
 * Eksekusi PPOB via MCP: TANPA PIN (PIN hanya via web/app resmi).
 * Dijalankan hanya bila McpPolicyService::check() lolos + scope purchase.
 * Pola ledger/tx/idempotency sama dengan PpobService::purchase().
 */
final class McpPurchaseService {
    /** Buat persiapan (tanpa debit). @return array{ok:bool,...} */
    public static function prepare(int $userId, string $productCode, string $customerNo): array {
        $customerNo = preg_replace('/\s+/', '', trim($customerNo));
        if ($customerNo === '' || strlen($customerNo) > 40) return ['ok' => false, 'message' => 'Nomor tujuan tidak valid (maks 40 karakter).'];
        if (!preg_match('/^[0-9a-zA-Z\-+]{3,40}$/', $customerNo)) return ['ok' => false, 'message' => 'Format nomor tujuan tidak valid.'];

        $prod = DB::one('SELECT p.*, pp.sell_price, c.code cat_code, c.name cat_name FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id WHERE p.code=? AND p.is_active=1 AND c.is_active=1', [$productCode]);
        if (!$prod || empty($prod['sell_price'])) return ['ok' => false, 'message' => 'Produk tidak tersedia atau sedang nonaktif.'];
        $sell = (int)$prod['sell_price'];

        // Harga dihitung ulang server — snapshot prepare hanya untuk tampilan.
        $check = McpPolicyService::check($userId, (string)$prod['cat_code'], $sell, $customerNo);
        $ttl = max(5, min(30, Settings::int('mcp_prepare_ttl_minutes', 10)));
        $prepId = 'mcp_' . bin2hex(random_bytes(12));
        try {
            DB::exec(
                'INSERT INTO mcp_preparations (id, user_id, product_code, customer_no, sell_price, status, expires_at) VALUES (?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL ' . $ttl . ' MINUTE))',
                [$prepId, $userId, $productCode, $customerNo, $sell, 'prepared']
            );
        } catch (\Throwable $e) { return ['ok' => false, 'message' => 'Gagal menyiapkan transaksi.']; }

        Logger::audit($userId, 'user', 'mcp.prepare', 'mcp_preparations', $prepId, 'ok', ['code' => $productCode]);
        return ['ok' => true, 'preparation' => [
            'preparation_id' => $prepId,
            'product' => ['code' => $prod['code'], 'name' => $prod['name'], 'category' => $prod['cat_code']],
            'customer_no' => $customerNo,
            'sell_price' => $sell, 'sell_price_rp' => rupiah($sell),
            'total_debit' => $sell, 'total_debit_rp' => rupiah($sell),
            'expires_in_minutes' => $ttl,
            'policy' => ['allowed' => $check['ok'], 'reason' => $check['reason']],
            'note' => $check['ok']
                ? 'Siap dieksekusi via execute_ppob_purchase.'
                : 'Belum bisa dieksekusi: ' . $check['reason'],
        ]];
    }

    /** Eksekusi persiapan. @return array{ok:bool,...} */
    public static function execute(int $userId, string $prepId, string $idempotencyKey = ''): array {
        if (!preg_match('/^mcp_[a-f0-9]{24}$/', $prepId)) return ['ok' => false, 'message' => 'preparation_id tidak valid.'];
        if ($idempotencyKey !== '' && !preg_match('/^[A-Za-z0-9\-_:.]{8,80}$/', $idempotencyKey)) {
            return ['ok' => false, 'message' => 'idempotency_key tidak valid.'];
        }

        // Idempotency: kembalikan transaksi lama untuk key yang sama.
        if ($idempotencyKey !== '') {
            $dup = DB::one('SELECT response FROM idempotency_keys WHERE `key` = ? AND user_id = ?', [$idempotencyKey, $userId]);
            if ($dup && !empty($dup['response'])) {
                $prev = json_decode((string)$dup['response'], true);
                if (is_array($prev) && isset($prev['reference_id'])) {
                    $t = DB::one('SELECT reference_id, kind, amount, total, status, note, created_at FROM transactions WHERE reference_id = ? AND user_id = ?', [$prev['reference_id'], $userId]);
                    if ($t) return ['ok' => true, 'duplicate' => true, 'transaction' => $t];
                }
            }
        }

        $prep = DB::one('SELECT * FROM mcp_preparations WHERE id = ? AND user_id = ? LIMIT 1', [$prepId, $userId]);
        if (!$prep) return ['ok' => false, 'message' => 'Persiapan tidak ditemukan atau bukan milik akun ini.'];
        if (($prep['status'] ?? '') !== 'prepared') return ['ok' => false, 'message' => 'Persiapan sudah dipakai/kedaluwarsa. Buat persiapan baru.'];
        if (strtotime((string)$prep['expires_at']) <= time()) {
            DB::exec("UPDATE mcp_preparations SET status='expired' WHERE id=?", [$prepId]);
            return ['ok' => false, 'message' => 'Persiapan kedaluwarsa. Buat persiapan baru via prepare_ppob_purchase.'];
        }

        // Hitung ulang harga aktual (anti race/manipulasi).
        $prod = DB::one('SELECT p.*, pp.sell_price, c.code cat_code FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id WHERE p.code=? AND p.is_active=1 AND c.is_active=1', [$prep['product_code']]);
        if (!$prod || empty($prod['sell_price'])) return ['ok' => false, 'message' => 'Produk berubah menjadi nonaktif. Persiapan dibatalkan.'];
        $sell = (int)$prod['sell_price'];
        if ($sell !== (int)$prep['sell_price']) {
            DB::exec("UPDATE mcp_preparations SET status='expired' WHERE id=?", [$prepId]);
            return ['ok' => false, 'message' => 'Harga berubah (' . rupiah((int)$prep['sell_price']) . ' → ' . rupiah($sell) . '). Buat persiapan baru.'];
        }

        $check = McpPolicyService::check($userId, (string)$prod['cat_code'], $sell, (string)$prep['customer_no']);
        if (!$check['ok']) {
            NotificationService::send($userId, 'mcp', 'Pembelian AI ditolak', mb_substr($check['reason'], 0, 200));
            Logger::audit($userId, 'user', 'mcp.execute.denied', 'mcp_preparations', $prepId, 'fail', ['reason' => mb_substr($check['reason'], 0, 120)]);
            return ['ok' => false, 'message' => $check['reason']];
        }

        $pp = DB::one('SELECT * FROM provider_products WHERE provider_sku = ? OR product_id = ? ORDER BY id DESC LIMIT 1', [$prep['product_code'], (int)$prod['id']]);
        $sku = $pp['provider_sku'] ?? $prep['product_code'];
        $base = (int)($pp['base_price'] ?? max(0, $sell - 1500));
        $ref = new_reference('PPOB');
        $customerNo = (string)$prep['customer_no'];

        try {
            DB::tx(function () use ($userId, $sell, $ref, $idempotencyKey, $prod, $customerNo, $sku, $base, $prepId) {
                $pdo = DB::pdo();
                // Lock policy implisit via user row + wallet lock di append().
                if (WalletService::balance($userId) < $sell) throw new \RuntimeException('Saldo tidak mencukupi.');
                $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, idempotency_key, note) VALUES (?,?, 'ppob', ?, 0, ?, 'processing',?,?)")->execute([
                    $ref, $userId, $sell, $sell, $idempotencyKey !== '' ? $idempotencyKey : null, $prod['name'] . ' / ' . $customerNo . ' [mcp]',
                ]);
                $txId = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO transaction_items (transaction_id, label, qty, unit_price, subtotal) VALUES (?,?,?,?,?)')->execute([$txId, $prod['name'] . ' — ' . $customerNo, 1, $sell, $sell]);
                $pdo->prepare('INSERT INTO provider_transactions (transaction_id, provider_id, provider_sku, customer_no, base_price, sell_price, status) VALUES (?, (SELECT id FROM providers WHERE code="digiflazz"), ?,?,?,?, ?)')->execute([$txId, $sku, $customerNo, $base, $sell, 'pending']);
                WalletService::append($userId, 'MCP-' . $ref, $ref, 'ppob', 'debit', $sell, ['destination' => 'digiflazz-pending', 'status' => 'pending', 'metadata' => ['channel' => 'mcp', 'preparation' => $prepId]]);
                $pdo->prepare("UPDATE mcp_preparations SET status='used', used_at=NOW() WHERE id=? AND status='prepared'")->execute([$prepId]);
                if ($idempotencyKey !== '') {
                    $pdo->prepare('INSERT IGNORE INTO idempotency_keys (`key`, user_id, response) VALUES (?,?,?)')->execute([$idempotencyKey, $userId, json_encode(['reference_id' => $ref])]);
                }
            });
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            Logger::log('mcp.execute.error', ['ref' => $ref]);
            return ['ok' => false, 'message' => 'Transaksi sedang diproses.'];
        }

        $api = (new DigiFlazzClient())->transaction($sku, $customerNo, $ref);
        if (!$api['ok']) {
            // Timeout/tidak merespons → PENDING, bukan auto-refund buta (mungkin sudah diproses provider).
            DB::exec("UPDATE transactions SET status='pending' WHERE reference_id=?", [$ref]);
            NotificationService::send($userId, 'mcp', 'Pembelian AI pending', $prod['name'] . ' menunggu provider. Ref: ' . $ref);
            Logger::audit($userId, 'user', 'mcp.execute.pending', 'transactions', $ref);
            $t = DB::one('SELECT reference_id, kind, amount, total, status, note, created_at FROM transactions WHERE reference_id = ?', [$ref]);
            return ['ok' => true, 'pending' => true, 'transaction' => $t, 'note' => 'Provider tidak merespons tepat waktu. Status pending — jangan ulangi pembelian; cek via get_my_transaction_status.'];
        }
        $status = strtoupper((string)($api['data']['status'] ?? 'PENDING'));
        DB::exec('UPDATE provider_transactions SET response = ?, status = ?, provider_ref = ? WHERE transaction_id = (SELECT id FROM transactions WHERE reference_id = ?)', [
            json_encode($api['data'], JSON_UNESCAPED_UNICODE), strtolower($status), (string)($api['data']['sn'] ?? $api['data']['ref_id'] ?? ''), $ref,
        ]);
        if ($status === 'SUKSES') {
            DB::exec("UPDATE transactions SET status='success' WHERE reference_id=?", [$ref]);
            NotificationService::send($userId, 'mcp', 'Pembelian AI berhasil', $prod['name'] . ' ' . $customerNo);
        } elseif (in_array($status, ['GAGAL', 'FAILED'], true)) {
            PpobService::refund($userId, $ref, 'Gagal dari provider (via AI)');
        } else {
            DB::exec("UPDATE transactions SET status='pending' WHERE reference_id=?", [$ref]);
            NotificationService::send($userId, 'mcp', 'Pembelian AI diproses', $prod['name'] . ' menunggu provider. Ref: ' . $ref);
        }
        Logger::audit($userId, 'user', 'mcp.execute', 'transactions', $ref);
        $t = DB::one('SELECT reference_id, kind, amount, total, status, note, created_at FROM transactions WHERE reference_id = ?', [$ref]);
        return ['ok' => true, 'transaction' => $t];
    }
}
