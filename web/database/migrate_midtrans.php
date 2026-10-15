<?php
declare(strict_types=1);
/**
 * Runner migrasi Midtrans — idempoten, CLI maupun browser super-admin.
 * Penggunaan CLI: php web/database/migrate_midtrans.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan via CLI atau panel admin.');
}
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\DB;

function col_exists(string $table, string $col): bool {
    try {
        $rows = DB::all("SHOW COLUMNS FROM `{$table}` LIKE ?", [$col]);
        return count($rows) > 0;
    } catch (Throwable $e) { return false; }
}
function idx_exists(string $table, string $idx): bool {
    try {
        $rows = DB::all("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$idx]);
        return count($rows) > 0;
    } catch (Throwable $e) { return false; }
}

$pdo = DB::pdo();
$added = [];
$wanted = [
    'gateway' => "VARCHAR(20) NOT NULL DEFAULT 'midtrans'",
    'payment_type' => "VARCHAR(30) NOT NULL DEFAULT 'qris'",
    'payment_code' => "VARCHAR(80) NULL",
    'qr_string' => "TEXT NULL",
    'actions' => "JSON NULL",
    'raw_response' => "JSON NULL",
    'credited_at' => "DATETIME NULL",
];
foreach ($wanted as $col => $def) {
    if (!col_exists('deposits', $col)) {
        $pdo->exec("ALTER TABLE deposits ADD COLUMN `{$col}` {$def}");
        $added[] = $col;
    }
}
foreach (['idx_dep_gateway' => '(gateway, status)', 'idx_dep_expired' => '(status, expired_at)'] as $idx => $cols) {
    if (!idx_exists('deposits', $idx)) {
        try { $pdo->exec("CREATE INDEX `{$idx}` ON deposits {$cols}"); } catch (Throwable $e) {}
    }
}

$seeds = [
    'deposit_enabled' => '1',
    'deposit_gateway' => 'midtrans',
    'deposit_methods' => 'qris,bca,bri,mandiri,permata,bni,gopay,shopeepay',
    'deposit_expiry_minutes' => '15',
    'midtrans_mode' => 'sandbox',
    'midtrans_fee_qris_pct' => '0.7',
    'midtrans_fee_bca' => '4000',
    'midtrans_fee_bri' => '4000',
    'midtrans_fee_bni' => '4000',
    'midtrans_fee_mandiri' => '4000',
    'midtrans_fee_permata' => '4000',
    'midtrans_fee_gopay_pct' => '2',
    'midtrans_fee_shopeepay_pct' => '2',
];
foreach ($seeds as $k => $v) {
    DB::exec('INSERT IGNORE INTO system_settings (`key`,`value`) VALUES (?,?)', [$k, $v]);
}
echo 'OK added=[' . implode(',', $added) . "]\n";
