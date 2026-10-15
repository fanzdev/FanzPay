<?php
declare(strict_types=1);
/**
 * Uji lokal integrasi Midtrans (tanpa network, tanpa kredensial).
 * Jalankan: php web/tests/midtrans_local.php
 * Menguji: signature SHA512 resmi, fee/quote, payload per-metode, parsing
 * respons, idempotency deposit mock, webhook settlement duplikat, guard nominal.
 */
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\DB;
use App\Providers\MidtransClient;
use App\Services\MidtransService;

$pass = 0; $fail = 0;
function t(string $name, callable $fn): void {
    global $pass, $fail;
    try { $fn(); $pass++; echo "PASS {$name}\n"; }
    catch (Throwable $e) { $fail++; echo "FAIL {$name} — " . substr($e->getMessage(), 0, 250) . "\n"; }
}
function assert_true(bool $v, string $m = 'assert'): void { if (!$v) throw new RuntimeException($m); }

// 1. Signature SHA512 sesuai dokumen Midtrans
t('signature-sha512', function () {
    $key = 'SB-Mid-server-test123';
    $sig = hash('sha512', 'ORDER-1' . '200' . '15000' . $key);
    assert_true(MidtransClient::verifySignatureWith($key, 'ORDER-1', '200', '15000', $sig), 'valid ditolak');
    assert_true(MidtransClient::verifySignatureWith($key, 'ORDER-1', '200', '15001', $sig) === false, 'nominal diubah lolos');
    assert_true(MidtransClient::verifySignatureWith($key, 'ORDER-1', '200', '15000', 'deadbeef') === false, 'sig palsu lolos');
    assert_true(MidtransClient::verifySignatureWith('', 'ORDER-1', '200', '15000', $sig) === false, 'key kosong lolos');
});

// 2. Fee transparan: integer, qris % vs VA flat
t('fee-quote', function () {
    $q = MidtransService::quote(100000, 'qris');
    assert_true($q['total'] === $q['amount'] + $q['fee'] && is_int($q['total']), 'total!=amount+fee');
    $va = MidtransService::quote(100000, 'bca');
    assert_true($va['fee'] >= 0 && $va['total'] === 100000 + $va['fee'], 'va fee');
    assert_true(MidtransService::feeFor('nope', 100000) === 0, 'metode asing berfee');
});

// 3. Payload per-metode sesuai Core API + expiry 15 mnt
t('payload-methods', function () {
    $p = MidtransService::buildPayload('qris', 'DEP-TEST-1', 15000, 15);
    assert_true($p['payment_type'] === 'qris' && $p['custom_expiry']['expiry_duration'] === 15 && $p['custom_expiry']['unit'] === 'minute', 'qris payload');
    $v = MidtransService::buildPayload('bca', 'DEP-TEST-2', 54000, 15);
    assert_true($v['payment_type'] === 'bank_transfer' && $v['bank_transfer']['bank'] === 'bca', 'va payload');
    $m = MidtransService::buildPayload('mandiri', 'DEP-TEST-3', 54000, 15);
    assert_true($m['payment_type'] === 'echannel' && isset($m['echannel']), 'mandiri payload');
    $g = MidtransService::buildPayload('gopay', 'DEP-TEST-4', 52000, 15);
    assert_true($g['payment_type'] === 'gopay', 'gopay payload');
    assert_true($p['transaction_details']['gross_amount'] === 15000, 'gross!=total');
});

// 4. Parsing respons charge
t('parse-response', function () {
    $r = MidtransService::parseChargeResponse('bca', ['va_numbers' => [['bank' => 'bca', 'va_number' => '3900812345678']]]);
    assert_true($r['payment_code'] === '3900812345678', 'va parse');
    $q = MidtransService::parseChargeResponse('qris', ['qr_string' => 'QR123', 'actions' => [['name' => 'generate-qr-code', 'url' => 'https://qr/x']]]);
    assert_true($q['qr_string'] === 'QR123' && $q['qr_url'] === 'https://qr/x', 'qris parse');
});

// 5. DB: deposit mock + webhook settlement + duplikat + nominal mismatch (cleanup manual)
t('db-midtrans-webhook', function () {
    $pdo = DB::pdo();
    $hasGw = count(DB::all('SHOW COLUMNS FROM deposits LIKE "gateway"')) > 0;
    if (!$hasGw) throw new RuntimeException('kolom gateway belum ada');
    $suffix = bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_MT1','089999000101','_mt1@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMT' . strtoupper($suffix)]);
    $uid = (int)$pdo->lastInsertId();
    $refs = [];
    try {
        $r = MidtransService::create($uid, 50000, 'qris', 'idem-test-' . $suffix);
        assert_true($r['ok'] && isset($r['deposit']['reference_id']), 'create: ' . ($r['message'] ?? ''));
        $ref = $r['deposit']['reference_id'];
        $refs[] = $ref;
        $dep = DB::one('SELECT * FROM deposits WHERE reference_id=?', [$ref]);
        assert_true((int)$dep['total_amount'] === (int)$dep['amount'] + (int)$dep['fee'], 'total tersimpan salah');
        // idempotency: kunci sama → ref sama
        $idemKey = 'idem-dup-' . $suffix;
        $a1 = MidtransService::create($uid, 50000, 'qris', $idemKey);
        $a2 = MidtransService::create($uid, 50000, 'qris', $idemKey);
        assert_true($a1['deposit']['reference_id'] === $a2['deposit']['reference_id'], 'idempotency jebol');
        $refs[] = $a1['deposit']['reference_id'];
        // webhook settlement sukses
        $w1 = MidtransService::applyNotification(['order_id' => $ref, 'transaction_status' => 'settlement', 'gross_amount' => (string)$dep['total_amount'], 'transaction_id' => 'MT-1', 'payment_type' => 'qris']);
        assert_true($w1['applied'] === true, 'settlement tidak teraplikasi');
        $bal = (int)DB::one('SELECT balance FROM wallets WHERE user_id=?', [$uid])['balance'];
        assert_true($bal === (int)$dep['credit_amount'], 'saldo=' . $bal);
        // duplikat: tidak kredit dua kali
        $w2 = MidtransService::applyNotification(['order_id' => $ref, 'transaction_status' => 'settlement', 'gross_amount' => (string)$dep['total_amount'], 'transaction_id' => 'MT-1', 'payment_type' => 'qris']);
        assert_true($w2['applied'] === false, 'duplikat mengkredit ulang');
        $bal2 = (int)DB::one('SELECT balance FROM wallets WHERE user_id=?', [$uid])['balance'];
        assert_true($bal2 === $bal, 'saldo berubah saat duplikat');
        // nominal mismatch: ditolak
        $r3 = MidtransService::create($uid, 60000, 'bca', 'idem-x-' . $suffix);
        $ref3 = $r3['deposit']['reference_id'];
        $refs[] = $ref3;
        $dep3 = DB::one('SELECT * FROM deposits WHERE reference_id=?', [$ref3]);
        $w3 = MidtransService::applyNotification(['order_id' => $ref3, 'transaction_status' => 'settlement', 'gross_amount' => (string)((int)$dep3['total_amount'] + 1000), 'transaction_id' => 'MT-9', 'payment_type' => 'bank_transfer']);
        assert_true($w3['applied'] === false, 'nominal mismatch lolos');
        assert_true(DB::one('SELECT status FROM deposits WHERE reference_id=?', [$ref3])['status'] === 'pending', 'status berubah saat mismatch');
        // order tak dikenal: diabaikan
        $w4 = MidtransService::applyNotification(['order_id' => 'DEP-TIDAK-ADA', 'transaction_status' => 'settlement', 'gross_amount' => '10000', 'transaction_id' => 'MT-0']);
        assert_true($w4['applied'] === false, 'order asing diproses');
    } finally {
        foreach ($refs as $rf) {
            try {
                $t = DB::one('SELECT id, transaction_id, user_id FROM deposits WHERE reference_id=?', [$rf]);
                if ($t) {
                    DB::exec('DELETE FROM wallet_ledger WHERE reference_id=?', [$rf]);
                    DB::exec('DELETE FROM deposits WHERE id=?', [(int)$t['id']]);
                    DB::exec('DELETE FROM transactions WHERE id=?', [(int)$t['transaction_id']]);
                }
            } catch (Throwable $e) {}
        }
        try {
            DB::exec('DELETE FROM idempotency_keys WHERE user_id=?', [$uid]);
            DB::exec('DELETE FROM notifications WHERE user_id=?', [$uid]);
            DB::exec("DELETE FROM webhook_events WHERE provider='midtrans' AND (delivery_id LIKE 'DEP-%' OR delivery_id='MT-1' OR delivery_id='MT-9')");
            DB::exec('DELETE FROM wallets WHERE user_id=?', [$uid]);
            DB::exec('DELETE FROM audit_logs WHERE actor_id=?', [$uid]);
            DB::exec('DELETE FROM security_events WHERE user_id=?', [$uid]);
            DB::exec('DELETE FROM users WHERE id=?', [$uid]);
        } catch (Throwable $e) {}
    }
});

// 6. Validasi batas: min, max, metode asing (tanpa sisa data)
t('validation', function () {
    $pdo = DB::pdo();
    $suffix = bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_MT2','089999000102','_mt2@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMV' . strtoupper($suffix)]);
    $uid = (int)$pdo->lastInsertId();
    try {
        assert_true(MidtransService::create($uid, 5000, 'qris')['ok'] === false, 'di bawah minimum lolos');
        assert_true(MidtransService::create($uid, 999999999, 'qris')['ok'] === false, 'di atas maksimum lolos');
        assert_true(MidtransService::create($uid, 50000, 'bitcoin')['ok'] === false, 'metode asing lolos');
    } finally {
        try { DB::exec('DELETE FROM users WHERE id=?', [$uid]); } catch (Throwable $e) {}
    }
});

echo "\n{$pass} lulus, {$fail} gagal\n";
exit($fail > 0 ? 1 : 0);
