<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Providers\DigiFlazzClient;

/**
 * Sinkronisasi katalog REAL dari DigiFlazz → provider_products + products + product_prices.
 * - Hanya item berstatus jual Aktif yang diaktifkan.
 * - harga_jual = base_price + margin (aturan produk > kategori > default_margin).
 * - Kategori DigiFlazz dipetakan ke kategori lokal; yang tak dikenal dibuatkan
 *   kategori "Lainnya…" otomatis agar tidak ada produk hilang.
 * Dipakai oleh: web/sync.php (CLI) dan Admin → Produk → Sinkron.
 */
final class CatalogSyncService {
    /** @return array{ok:bool, message?:string, skus?:int, active?:int} */
    public static function syncPrepaid(?DigiFlazzClient $client = null): array {
        $client = $client ?? new DigiFlazzClient();
        $api = $client->priceList('prepaid');
        if (!$api['ok']) return ['ok' => false, 'message' => (string)($api['message'] ?? 'Gagal mengambil price-list.')];
        $raw = $api['data']['data'] ?? $api['data'] ?? [];
        $items = is_array($raw) ? array_values(array_filter($raw, 'is_array')) : [];
        if (!$items) return ['ok' => false, 'message' => 'Price-list kosong dari provider.'];

        $skus = 0; $active = 0;
        // Prepared statement dipakai ulang + commit per batch agar sinkronisasi
        // ribuan SKU selesai dalam hitungan detik, bukan menit.
        $pdo = DB::pdo();
        $qCat = $pdo->prepare('SELECT id FROM product_categories WHERE code = ?');
        $qFind = $pdo->prepare('SELECT id FROM products WHERE code = ?');
        $qInsProd = $pdo->prepare('INSERT INTO products (category_id, code, name, description, is_active, is_featured) VALUES (?,?,?,?,?,0)');
        $qUpdProd = $pdo->prepare('UPDATE products SET category_id = ?, name = ?, is_active = ? WHERE id = ?');
        $qPrice = $pdo->prepare('INSERT INTO product_prices (product_id, sell_price, margin) VALUES (?,?,?) ON DUPLICATE KEY UPDATE sell_price=VALUES(sell_price), margin=VALUES(margin)');
        $qProv = $pdo->prepare('INSERT INTO provider_products (provider_id, provider_sku, product_id, name, base_price, category, status, raw, synced_at) VALUES ((SELECT id FROM providers WHERE code="digiflazz"),?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE product_id=VALUES(product_id), name=VALUES(name), base_price=VALUES(base_price), category=VALUES(category), status=VALUES(status), raw=VALUES(raw), synced_at=NOW()');
        $n = 0;
        $pdo->beginTransaction();
        try {
        foreach ($items as $it) {
            $sku = trim((string)($it['buyer_sku_code'] ?? ''));
            if ($sku === '') continue;
            $base = (int)($it['price'] ?? 0);
            $name = mb_substr(trim((string)($it['product_name'] ?? $sku)), 0, 150);
            $catRaw = mb_substr(trim((string)($it['category'] ?? 'Lainnya')), 0, 60);
            $brand = mb_substr(trim((string)($it['brand'] ?? '')), 0, 255);
            $sellable = self::isOn($it['buyer_product_status'] ?? false)
                && !self::isOff($it['seller_product_status'] ?? true)
                && (($it['unlimited_stock'] ?? true) || (int)($it['stock'] ?? 1) > 0);
            $catId = self::categoryIdTx($pdo, $qCat, $catRaw);
            $qFind->execute([$sku]);
            $pid = (int)($qFind->fetchColumn() ?: 0);
            if ($pid === 0) {
                $qInsProd->execute([$catId, $sku, $name, $brand, $sellable ? 1 : 0]);
                $pid = (int)$pdo->lastInsertId();
            } else {
                $qUpdProd->execute([$catId, $name, $sellable ? 1 : 0, $pid]);
            }
            $sell = PricingService::compute($base, $pid, $catId);
            $qPrice->execute([$pid, $sell, max(0, $sell - $base)]);
            $qProv->execute([$sku, $pid, $name, $base, $catRaw, $sellable ? 'active' : 'inactive', json_encode($it, JSON_UNESCAPED_UNICODE)]);
            $skus++;
            if ($sellable) $active++;
            if (++$n % 300 === 0) { $pdo->commit(); $pdo->beginTransaction(); }
        }
        $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            Logger::log('catalogsync.error', ['at' => $skus]);
            return ['ok' => false, 'message' => 'Sinkronisasi terputus di tengah jalan.'];
        }
        try { DB::exec('UPDATE providers SET last_checked_at = NOW(), status = ? WHERE code = ?', ['online', 'digiflazz']); } catch (\Throwable $e) {}
        Logger::log('catalogsync.done', ['skus' => $skus, 'active' => $active]);
        return ['ok' => true, 'skus' => $skus, 'active' => $active];
    }

    /** Status provider bisa boolean (true/false) atau string "Aktif". */
    private static function isOn($v): bool {
        if ($v === true || $v === 1 || $v === '1') return true;
        return in_array(mb_strtolower(trim((string)$v)), ['aktif', 'active', 'true', 'available', 'normal'], true);
    }

    private static function isOff($v): bool {
        if ($v === false || $v === 0 || $v === '0') return true;
        return in_array(mb_strtolower(trim((string)$v)), ['tidak aktif', 'inactive', 'false', 'gangguan', 'maintenance'], true);
    }

    private static function categoryIdTx(\PDO $pdo, \PDOStatement $qCat, string $raw): int {
        $l = mb_strtolower($raw);
        $map = [
            'pulsa' => 'pulsa', 'data' => 'data', 'internet' => 'data',
            'pln' => 'pln', 'token' => 'pln', 'listrik' => 'pln',
            'emoney' => 'emoney', 'e-money' => 'emoney', 'e money' => 'emoney',
            'saldo' => 'emoney', 'ovo' => 'emoney', 'dana' => 'emoney', 'gopay' => 'emoney',
            'game' => 'game', 'voucher' => 'game', 'games' => 'game',
            'tv' => 'tv', 'pdam' => 'pdam', 'air' => 'pdam',
            'bpjs' => 'bpjs', 'kesehatan' => 'bpjs',
        ];
        foreach ($map as $needle => $code) {
            if (str_contains($l, $needle)) {
                $qCat->execute([$code]);
                $id = $qCat->fetchColumn() ?: null;
                if ($id) return (int)$id;
            }
        }
        $slug = 'cat-' . substr(md5($l), 0, 8);
        $qCat->execute([$slug]);
        $id = $qCat->fetchColumn() ?: null;
        if (!$id) {
            $pdo->prepare('INSERT INTO product_categories (code, name, icon, sort, is_active) VALUES (?,?,?,99,1)')->execute([$slug, mb_substr($raw, 0, 80), 'grid']);
            $id = (int)$pdo->lastInsertId();
        }
        return (int)$id;
    }
}
