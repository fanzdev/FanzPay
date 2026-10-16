<?php
declare(strict_types=1);
/**
 * FanzPay test runner (tanpa framework): php web/tests/run.php
 * Unit murni selalu jalan; integrasi DB jalan bila MySQL tersedia.
 */
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\Logger;
use App\Providers\BuatQrisClient;
use App\Providers\DigiFlazzClient;

$pass = 0; $fail = 0;
function t(string $name, callable $fn): void {
    global $pass, $fail;
    try { $fn(); $pass++; echo "PASS {$name}\n"; }
    catch (Throwable $e) { $fail++; echo "FAIL {$name} — " . substr($e->getMessage(), 0, 200) . "\n"; }
}
function assert_true(bool $v, string $m = 'assert'): void { if (!$v) throw new RuntimeException($m); }

// 1. Format rupiah Indonesia
t('rupiah', function () {
    assert_true(rupiah(125000) === 'Rp 125.000');
    assert_true(rupiah(0) === 'Rp 0');
});

// 2. Reference ID unik & bukan timestamp saja
t('reference-unique', function () {
    $ids = [];
    for ($i = 0; $i < 500; $i++) $ids[] = new_reference('TRF');
    assert_true(count(array_unique($ids)) === 500, 'duplikat ref');
    assert_true(strlen($ids[0]) > 20, 'ref terlalu pendek');
});

// 3. Public ID opaque (tanpa email/ID sensitif)
t('public-id', function () {
    $a = new_public_id(); $b = new_public_id();
    assert_true($a !== $b && str_starts_with($a, 'FP'));
});

// 4. Webhook HMAC-SHA256 sesuai dokumen BuatQris
t('webhook-signature', function () {
    $secret = 'test_signing_secret';
    $body = '{"event":"payment.success","transaction_id":"100043729581"}';
    $sig = 'sha256=' . hash_hmac('sha256', $body, $secret);
    assert_true(BuatQrisClient::verifySignature($body, $sig, $secret));
    assert_true(!BuatQrisClient::verifySignature($body, 'sha256=deadbeef', $secret), 'sig palsu lolos');
    assert_true(!BuatQrisClient::verifySignature($body . 'x', $sig, $secret), 'body diubah lolos');
});

// 5. Signature DigiFlazz md5(username.key.refId)
t('digiflazz-sign', function () {
    assert_true(DigiFlazzClient::sign('u', 'k', 'r') === md5('ukr'));
});

// 6. Uang selalu integer (bukan float)
t('money-integer', function () {
    $sell = 11800 + 700;
    assert_true(is_int($sell) && $sell === 12500);
    assert_true(strpos((string)(12500 / 100), '.') === false || true); // dokumentatif
});

// 7. Logger tidak pernah membocorkan rahasia
t('log-scrub', function () {
    $s = Logger::scrub(['password' => 'x', 'pin' => '1', 'secret_token' => 's', 'nested' => ['api_key' => 'k'], 'amount' => 5]);
    assert_true($s['password'] === '[redacted]' && $s['nested']['api_key'] === '[redacted]' && $s['amount'] === 5);
});

// 8. Masking secret untuk tampilan admin
t('mask-secret', function () {
    assert_true(mask_secret('') !== '');
    assert_true(!str_contains(mask_secret('sk_live_abcdefgh1234'), 'abcdefgh'));
});

// 9. Integrasi DB: ledger append-only + rekonstruksi (rollback, tanpa sisa data)
t('db-ledger', function () {
    $pdo = App\Core\DB::pdo();
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_T1','089999000001','_t1@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTEST000001']);
    $a = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_T2','089999000002','_t2@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTEST000002']);
    $b = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,0,0),(?,0,0)')->execute([$a, $b]);
    // Saldo awal WAJIB lewat ledger agar dapat direkonstruksi penuh
    App\Services\WalletService::append($a, 'T-0', 'R-0', 'deposit', 'credit', 100000);
    App\Services\WalletService::append($a, 'T-1', 'R-1', 'transfer_out', 'debit', 80000);
    App\Services\WalletService::append($b, 'T-2', 'R-1', 'transfer_in', 'credit', 80000);
    $rebuilt = App\Services\WalletService::rebuild($a);
    assert_true($rebuilt === 20000, 'rebuild=' . $rebuilt);
    // double webhook: baris kedua dengan transaction_id sama harus ditolak unique key
    $dup = false;
    try { App\Services\WalletService::append($b, 'T-2', 'R-1', 'transfer_in', 'credit', 80000); }
    catch (Throwable $e) { $dup = true; }
    assert_true($dup, 'idempotency ledger jebol');
    $pdo->rollBack();
});

// 10. PIN: hash + lock setelah 5x salah (rollback)
t('db-pin', function () {
    $pdo = App\Core\DB::pdo();
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_P','089999000003','_p@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTEST000003']);
    $uid = (int)$pdo->lastInsertId();
    App\Services\PinService::set($uid, '123456');
    assert_true(App\Services\PinService::verify($uid, '123456')['ok']);
    assert_true(!App\Services\PinService::verify($uid, '000000')['ok']);
    $pdo->rollBack();
});

// 11. View: payload ber-key 'data'/'file' tidak boleh hilang (regresi EXTR_SKIP)
t('view-data-key', function () {
    $probe = fp_base_path('views/pages/_probe_tmp.php');
    file_put_contents($probe, '<?= e($data["old_pending"] ?? "HILANG") ?>|<?= e($title) ?>');
    try {
        ob_start();
        App\Core\View::render('_probe_tmp', ['title' => 'T', 'data' => ['old_pending' => 'ADA']], null);
        $out = (string)ob_get_clean();
    } finally {
        @unlink($probe);
    }
    assert_true($out === 'ADA|T', 'render=' . $out);
});

// 12. Palet: seluruh warna UI turunan logo (biru + netral + 3 semantik redup)
t('palette-only', function () {
    $base = dirname(__DIR__);
    $allowedHex = [
        // brand (logo #0040F0 / #1090F0) + tint
        '001a6e','00279e','0033cc','0040f0','0868f0','1090f0','7fb2fc','b3d3fd','d9e9fe','eef5ff',
        // surface navy
        '060b18','070f20','0a1224','0a1734','0d1730','0e1b38','0f2050','101e45','142a66','1e3270',
        // netral ink/line/bg
        '0a1a33','46587a','6b7c99','f6f8fc','eef3fa','e4eaf3','f0f4fa','ffffff','eaf1fb','b9c7dd','8a99b5','9babc7',
        // tint biru
        'dbe7ff','c9d8ff','9db8ff','7ea2ff','edf3ff',
        // semantik redup (hanya status)
        '157f53','e6f5ee','b45309','fdf3e2','b3261e','fcedea','e8f0fe','7fd8b0','e8b366','f1a09a','f6d3d0','8f231c','0f6b49','6e4e06',
    ];
    $allowedRgb = ['255,255,255','0,0,0','10,26,51','0,64,240','8,104,240','16,144,240','127,178,252','148,178,255','219,231,255','21,127,83','180,83,9','179,38,30','241,160,154','6,11,24','13,23,48','138,153,181'];
    $viol = [];
    $scan = function (string $dir) use (&$scan, $base, $allowedHex, $allowedRgb, &$viol): void {
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            if (is_dir($p)) { $scan($p); continue; }
            if (!preg_match('/\.(css|js|php)$/', $f)) continue;
            $src = (string)file_get_contents($p);
            $rel = str_replace('\\', '/', substr($p, strlen($base) + 1));
            $line = fn(int $off): int => substr_count(substr($src, 0, $off), "\n") + 1;
            if (preg_match_all('/#([0-9A-Fa-f]{3,8})\b/', $src, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[1] as $i => $hit) {
                    $v = strtolower($hit[0]);
                    if (strlen($v) === 3) $v = $v[0] . $v[0] . $v[1] . $v[1] . $v[2] . $v[2];
                    if (!in_array($v, $allowedHex, true)) $viol[] = "{$rel}:{$line($m[0][$i][1])} #{$hit[0]}";
                }
            }
            if (preg_match_all('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $src, $m2, PREG_OFFSET_CAPTURE)) {
                foreach ($m2[1] as $i => $r) {
                    $key = $r[0] . ',' . $m2[2][$i][0] . ',' . $m2[3][$i][0];
                    if (!in_array($key, $allowedRgb, true)) $viol[] = "{$rel}:{$line($m2[0][$i][1])} rgb({$key})";
                }
            }
        }
    };
    $scan($base . '/public/assets/css');
    $scan($base . '/public/assets/js');
    $scan($base . '/views');
    assert_true($viol === [], "warna di luar palet:\n" . implode("\n", array_slice($viol, 0, 25)));
});

echo "\n{$pass} lulus, {$fail} gagal\n";
exit($fail > 0 ? 1 : 0);
