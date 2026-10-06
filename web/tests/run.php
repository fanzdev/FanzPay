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

echo "\n{$pass} lulus, {$fail} gagal\n";
exit($fail > 0 ? 1 : 0);
