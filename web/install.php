<?php
declare(strict_types=1);

/**
 * Installer CLI: php web/install.php
 * Membuat database, import schema, seed akun awal + produk contoh.
 * Aman dijalankan ulang (idempotent).
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/helpers.php';

$cfg = require __DIR__ . '/config/config.php';
$host = $cfg['db']['host']; $port = $cfg['db']['port'];
$user = $cfg['db']['user']; $pass = $cfg['db']['pass']; $name = $cfg['db']['name'];

echo "FanzPay installer\n================\n";
$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "OK database `{$name}`\n";

$run = function (string $file) use ($host, $port, $user, $pass, $name) {
    $sql = file_get_contents($file);
    // pecah sederhana per statement (schema tidak mengandung DELIMITER)
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $sql)))) as $stmt) {
        if ($stmt === '' || str_starts_with($stmt, '--') && strlen($stmt) < 5) continue;
        try { $pdo->exec($stmt); } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), 'Duplicate')) echo 'WARN: ' . substr($e->getMessage(), 0, 120) . "\n";
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
};
$run(__DIR__ . '/database/schema.sql');
echo "OK schema\n";
$run(__DIR__ . '/database/seed.sql');
echo "OK seed statis\n";

$pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mk = function (string $role, string $name, string $phone, string $email, string $pass, string $public) use ($pdo) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) { echo "SKIP {$email} sudah ada\n"; return (int)$pdo->query("SELECT id FROM users WHERE email=" . $pdo->quote($email))->fetchColumn(); }
    $st = $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status,email_verified_at) VALUES (?,?,?,?,?,?,'active',NOW())");
    $st->execute([$role, $name, $phone, $email, $hash, $public]);
    $id = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT IGNORE INTO wallets (user_id,balance,locked) VALUES (?,?,?)')->execute([$id, 0, 0]);
    echo "OK {$role} {$email}\n";
    return $id;
};

// Super admin pertama HANYA dari .env (tidak ada akun demo/bawaan).
// Isi SUPERADMIN_NAME/EMAIL/PHONE/PASSWORD di web/.env lalu jalankan ulang installer,
// atau: php web/create-admin.php
$saEmail = (string)($_ENV['SUPERADMIN_EMAIL'] ?? getenv('SUPERADMIN_EMAIL') ?: '');
if ($saEmail !== '') {
    $mk('super_admin',
        (string)($_ENV['SUPERADMIN_NAME'] ?? getenv('SUPERADMIN_NAME') ?: 'Super Admin'),
        (string)($_ENV['SUPERADMIN_PHONE'] ?? getenv('SUPERADMIN_PHONE') ?: '081200000001'),
        $saEmail,
        (string)($_ENV['SUPERADMIN_PASSWORD'] ?? getenv('SUPERADMIN_PASSWORD') ?: ''),
        'FP' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10)));
} else {
    echo "INFO: SUPERADMIN_EMAIL kosong — lewati pembuatan akun. Jalankan: php web/create-admin.php\n";
}

$settings = [
    'site_name' => 'FanzPay',
    'maintenance_mode' => '0',
    'min_deposit' => '10000', 'max_deposit' => '5000000',
    'min_withdraw' => '20000', 'max_withdraw' => '5000000',
    'min_transfer' => '1000', 'max_transfer' => '10000000',
    'daily_tx_limit' => '50000000',
    'default_margin' => '1000',
    'provider_mode' => 'production',
    'transfer_fee' => '0',
    'deposit_enabled' => '1',
    'deposit_gateway' => 'midtrans',
    'deposit_methods' => 'qris,bca,bri,mandiri,permata,bni,gopay,shopeepay',
    'deposit_expiry_minutes' => '15',
    'midtrans_mode' => 'sandbox',
    'midtrans_fee_qris_pct' => '0.7',
    'midtrans_fee_bca' => '4000', 'midtrans_fee_bri' => '4000',
    'midtrans_fee_bni' => '4000', 'midtrans_fee_mandiri' => '4000',
    'midtrans_fee_permata' => '4000',
    'midtrans_fee_gopay_pct' => '2', 'midtrans_fee_shopeepay_pct' => '2',
];
$st = $pdo->prepare('INSERT INTO system_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=IF(`value`="",VALUES(`value`),`value`)');
foreach ($settings as $k => $v) $st->execute([$k, $v]);

echo "OK pengaturan\n";
echo "\nSelesai. Katalog produk diambil REAL dari DigiFlazz via: php web/sync.php\n";
