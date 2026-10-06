<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Pricing engine terpisah dari katalog.
 * harga_jual = harga_modal (base_price provider) + margin.
 * Urutan margin: product override > category rule > default 0.
 * User HANYA melihat harga jual.
 */
final class PricingService {
    public static function sellPrice(int $productId): ?int {
        $row = DB::one('SELECT sell_price FROM product_prices WHERE product_id = ?', [$productId]);
        return $row ? (int)$row['sell_price'] : null;
    }

    public static function compute(int $basePrice, ?int $productId = null, ?int $categoryId = null): int {
        if ($productId !== null) {
            $r = DB::one('SELECT sell_price FROM product_prices WHERE product_id = ?', [$productId]);
            if ($r) return (int)$r['sell_price'];
        }
        $margin = 0;
        if ($productId !== null) {
            $r = DB::one("SELECT margin FROM pricing_rules WHERE scope='product' AND scope_id=? AND is_active=1 ORDER BY id DESC LIMIT 1", [(string)$productId]);
            if ($r) $margin = (int)$r['margin'];
        }
        if ($margin === 0 && $categoryId !== null) {
            $r = DB::one("SELECT margin FROM pricing_rules WHERE scope='category' AND scope_id=? AND is_active=1 ORDER BY id DESC LIMIT 1", [(string)$categoryId]);
            if ($r) $margin = (int)$r['margin'];
        }
        if ($margin === 0) $margin = Settings::int('default_margin', 1000);
        return $basePrice + $margin;
    }

    /** @return array<int,array> katalog aktif + harga jual (tanpa modal/margin). */
    public static function catalog(?string $category = null): array {
        $sql = 'SELECT p.id, p.code, p.name, p.description, p.is_featured, c.code cat_code, c.name cat_name, pp.sell_price price
                FROM products p JOIN product_categories c ON c.id = p.category_id
                LEFT JOIN product_prices pp ON pp.product_id = p.id
                WHERE p.is_active = 1 AND c.is_active = 1';
        $params = [];
        if ($category) { $sql .= ' AND c.code = ?'; $params[] = $category; }
        $sql .= ' ORDER BY p.is_featured DESC, p.name ASC LIMIT 200';
        return DB::all($sql, $params);
    }
}
