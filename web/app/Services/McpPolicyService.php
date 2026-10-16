<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Kebijakan pembelian otomatis MCP (opt-in, default NONAKTIF).
 * Efektif = min(batas user, batas sistem Super Admin).
 */
final class McpPolicyService {
    public static function systemLimits(): array {
        return [
            'max_per_tx' => Settings::int('mcp_max_per_tx', 100000),
            'max_daily' => Settings::int('mcp_max_daily', 500000),
            'allowed_categories' => array_values(array_filter(array_map('trim', explode(',', strtolower((string)Settings::get('mcp_allowed_categories', 'pulsa,data,pln,emoney,game')))))),
        ];
    }

    /** @return array{enabled:bool,max_per_tx:int,max_daily:int,allowed_categories:array<int,string>,allow_new_numbers:bool} */
    public static function forUser(int $userId): array {
        try {
            $row = DB::one('SELECT * FROM mcp_purchase_policy WHERE user_id = ?', [$userId]);
        } catch (\Throwable $e) { $row = null; }
        $sys = self::systemLimits();
        if (!$row) {
            return ['enabled' => false, 'max_per_tx' => 0, 'max_daily' => 0, 'allowed_categories' => [], 'allow_new_numbers' => false, 'system' => $sys];
        }
        $cats = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', strtolower((string)($row['allowed_categories'] ?? ''))))),
            $sys['allowed_categories'] !== [] ? $sys['allowed_categories'] : ['pulsa', 'data', 'pln', 'emoney', 'game']
        ));
        return [
            'enabled' => ((int)$row['enabled'] === 1),
            'max_per_tx' => min((int)$row['max_per_tx'], $sys['max_per_tx']),
            'max_daily' => min((int)$row['max_daily'], $sys['max_daily']),
            'allowed_categories' => $cats,
            'allow_new_numbers' => ((int)$row['allow_new_numbers'] === 1),
            'system' => $sys,
        ];
    }

    /** Simpan kebijakan user (hanya via halaman web resmi; batas dijepit ke batas sistem). */
    public static function save(int $userId, bool $enabled, int $maxPerTx, int $maxDaily, array $categories, bool $allowNew): void {
        $sys = self::systemLimits();
        $maxPerTx = max(0, min($maxPerTx, $sys['max_per_tx']));
        $maxDaily = max(0, min($maxDaily, $sys['max_daily']));
        $allowed = $sys['allowed_categories'] !== [] ? $sys['allowed_categories'] : ['pulsa', 'data', 'pln', 'emoney', 'game'];
        $cats = implode(',', array_values(array_intersect(array_map('strtolower', $categories), $allowed)));
        DB::exec(
            'INSERT INTO mcp_purchase_policy (user_id, enabled, max_per_tx, max_daily, allowed_categories, allow_new_numbers) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), max_per_tx=VALUES(max_per_tx), max_daily=VALUES(max_daily), allowed_categories=VALUES(allowed_categories), allow_new_numbers=VALUES(allow_new_numbers)',
            [$userId, $enabled ? 1 : 0, $maxPerTx, $maxDaily, $cats, $allowNew ? 1 : 0]
        );
    }

    /** Akumulasi pembelian sukses hari ini (untuk batas harian). */
    public static function spentToday(int $userId, string $channel = 'mcp'): int {
        try {
            $r = DB::one(
                "SELECT COALESCE(SUM(total),0) s FROM transactions WHERE user_id = ? AND kind = 'ppob' AND status = 'success' AND DATE(created_at) = CURDATE() AND note LIKE ?",
                [$userId, $channel === 'mcp' ? '%[mcp]%' : '%']
            );
        } catch (\Throwable $e) { return 0; }
        return (int)($r['s'] ?? 0);
    }

    /**
     * Periksa 10 syarat pembelian otomatis. Kembalikan [ok, reason, policy].
     * @return array{ok:bool, reason:string, policy:array}
     */
    public static function check(int $userId, string $catCode, int $total, string $customerNo): array {
        $p = self::forUser($userId);
        if (!$p['enabled']) return ['ok' => false, 'reason' => 'Pembelian otomatis nonaktif. Aktifkan di halaman Koneksi AI (login web resmi) terlebih dahulu.', 'policy' => $p];
        if (!in_array(strtolower($catCode), $p['allowed_categories'], true)) {
            return ['ok' => false, 'reason' => 'Kategori produk tidak diizinkan oleh kebijakan Anda.', 'policy' => $p];
        }
        if ($total > $p['max_per_tx']) {
            return ['ok' => false, 'reason' => 'Nominal ' . rupiah($total) . ' melewati batas per transaksi ' . rupiah($p['max_per_tx']) . '.', 'policy' => $p];
        }
        if (self::spentToday($userId) + $total > $p['max_daily']) {
            return ['ok' => false, 'reason' => 'Melebihi batas harian ' . rupiah($p['max_daily']) . '. Coba lagi besok atau ubah batas di web resmi.', 'policy' => $p];
        }
        if (WalletService::balance($userId) < $total) {
            return ['ok' => false, 'reason' => 'Saldo tidak mencukupi. Isi saldo via deposit QRIS di aplikasi/web resmi.', 'policy' => $p];
        }
        if (!$p['allow_new_numbers']) {
            $seen = DB::one('SELECT 1 FROM provider_transactions pt JOIN transactions t ON t.id = pt.transaction_id WHERE t.user_id = ? AND pt.customer_no = ? LIMIT 1', [$userId, $customerNo]);
            if (!$seen) return ['ok' => false, 'reason' => 'Nomor tujuan baru belum diizinkan. Izinkan nomor baru di pengaturan atau beli via aplikasi resmi.', 'policy' => $p];
        }
        return ['ok' => true, 'reason' => '', 'policy' => $p];
    }
}
