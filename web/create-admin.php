<?php
declare(strict_types=1);
/**
 * CLI: php web/create-admin.php "Nama" email 08xxxx "PasswordKuat123!"
 * Membuat super_admin (atau: tambah argumen ke-5 = admin untuk admin biasa).
 * Dipakai karena TIDAK ada akun bawaan/demo.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/config/config.php';
require __DIR__ . '/app/Core/helpers.php';

[$script, $name, $email, $phone, $pass] = array_pad($argv, 5, '');
$role = ($argv[5] ?? 'super_admin') === 'admin' ? 'admin' : 'super_admin';
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
    exit("Pakai: php web/create-admin.php \"Nama\" email 08xxxx \"PasswordMin8\" [admin]\n");
}
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
try {
    App\Core\DB::tx(function () use ($role, $name, $phone, $email, $pass) {
        $pdo = App\Core\DB::pdo();
        if (App\Core\DB::one('SELECT id FROM users WHERE email = ?', [$email])) {
            throw new RuntimeException('Email sudah terdaftar.');
        }
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status,email_verified_at) VALUES (?,?,?,?,?,?,'active',NOW())")->execute([
            $role, $name, App\Core\Validator::normalizePhone($phone), $email, password_hash($pass, PASSWORD_DEFAULT), new_public_id(),
        ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,?,?)')->execute([$id, 0, 0]);
        echo "OK {$role} {$email} (id {$id})\n";
    });
} catch (Throwable $e) {
    exit('GAGAL: ' . $e->getMessage() . "\n");
}
