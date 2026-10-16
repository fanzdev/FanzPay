<?php
declare(strict_types=1);
/**
 * FanzPay MCP tests (tanpa framework): php web/tests/mcp_run.php
 * Unit murni + integrasi DB (rollback) + security. Ikuti gaya tests/run.php.
 */
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});

use App\Core\DB;
use App\Mcp\McpAuth;
use App\Mcp\McpException;
use App\Mcp\McpRunner;
use App\Mcp\McpServer;
use App\Mcp\McpTools;
use App\Services\McpPolicyService;

$pass = 0; $fail = 0;
function t(string $name, callable $fn): void {
    global $pass, $fail;
    try { $fn(); $pass++; echo "PASS {$name}\n"; }
    catch (Throwable $e) { $fail++; echo "FAIL {$name} — " . substr($e->getMessage(), 0, 250) . "\n"; }
}
function assert_true(bool $v, string $m = 'assert'): void { if (!$v) throw new RuntimeException($m); }

// ---- UNIT ----

t('mcp-tools-count', function () {
    assert_true(count(McpTools::definitions()) === 18, 'harus 18 tools, dapat ' . count(McpTools::definitions()));
});

t('mcp-no-pin-field', function () {
    foreach (McpTools::definitions() as $name => $d) {
        $s = json_encode(McpTools::inputSchema($name));
        assert_true(!preg_match('/"pin"|"otp"|"password"/i', $s), "tool {$name} punya field sensitif");
    }
});

t('mcp-pin-blocked', function () {
    try {
        McpTools::validate('execute_ppob_purchase', ['preparation_id' => 'mcp_' . str_repeat('a', 24), 'pin' => '123456']);
        throw new RuntimeException('PIN lolos validasi');
    } catch (McpException $e) { assert_true($e->getCode() === -32602); }
});

t('mcp-initialize', function () {
    $r = McpServer::dispatch(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []], null);
    assert_true(($r['result']['protocolVersion'] ?? '') === '2024-11-05');
    assert_true(($r['result']['serverInfo']['name'] ?? '') === 'fanzpay-mcp');
});

t('mcp-list-public', function () {
    $r = McpServer::dispatch(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => []], null);
    assert_true(count($r['result']['tools'] ?? []) === 18, 'list harus 18');
});

t('mcp-call-noauth', function () {
    $r = McpServer::dispatch(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get_my_balance', 'arguments' => []]], null);
    assert_true(isset($r['error']) && $r['error']['code'] === -32603, 'tanpa auth harus ditolak');
});

t('mcp-unknown-tool', function () {
    $r = McpServer::dispatch(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'transfer_saldo', 'arguments' => []]], null);
    assert_true(isset($r['error']), 'tool transfer tidak boleh ada');
});

t('mcp-bad-jsonrpc', function () {
    $r = McpServer::dispatch(['id' => 1, 'method' => 'ping'], null);
    assert_true($r['error']['code'] === -32600);
});

t('mcp-policy-default-off', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_M','089999000010','_m@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCP001']);
        $uid = (int)$pdo->lastInsertId();
        $p = McpPolicyService::forUser($uid);
        assert_true($p['enabled'] === false, 'default harus nonaktif');
        assert_true(true);
    } finally { $pdo->rollBack(); }
});

t('mcp-policy-clamp', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_M2','089999000011','_m2@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCP002']);
        $uid = (int)$pdo->lastInsertId();
        McpPolicyService::save($uid, true, 999999999, 999999999, ['pulsa', 'data'], true);
        $p = McpPolicyService::forUser($uid);
        $sys = McpPolicyService::systemLimits();
        assert_true($p['max_per_tx'] <= $sys['max_per_tx'], 'batas user melewati sistem');
        assert_true($p['max_daily'] <= $sys['max_daily'], 'harian melewati sistem');
    } finally { $pdo->rollBack(); }
});

t('mcp-token-issue-auth', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_M3','089999000012','_m3@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCP003']);
        $uid = (int)$pdo->lastInsertId();
        $tok = McpAuth::issue($uid, 'test', 'generic', ['read']);
        assert_true(str_starts_with($tok, 'fp_mcp_'));
        $found = McpAuth::fromMcpToken($tok);
        assert_true($found !== null && (int)$found['user']['id'] === $uid, 'token tidak mengikat user benar');
        assert_true($found['scopes'] === ['read']);
        // Token hanya sha256 di DB (tidak ada plaintext).
        $row = DB::one('SELECT token_hash FROM mcp_tokens WHERE user_id = ?', [$uid]);
        assert_true($row && $row['token_hash'] === hash('sha256', $tok) && !str_contains($row['token_hash'], 'fp_mcp_'));
        // Revokasi memutus auth.
        $tid = (int)DB::one('SELECT id FROM mcp_tokens WHERE user_id = ?', [$uid])['id'];
        McpAuth::revoke($uid, $tid);
        assert_true(McpAuth::fromMcpToken($tok) === null, 'token dicabut masih lolos');
    } finally { $pdo->rollBack(); }
});

t('mcp-cross-account', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_A','089999000020','_a@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCPA']);
        $a = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_B','089999000021','_b@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCPB']);
        $b = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,0,0),(?,0,0)')->execute([$a, $b]);
        $pdo->prepare("INSERT INTO transactions (reference_id, user_id, kind, amount, fee, total, status, note) VALUES (?,?,?,?,?,?,?,?)")->execute(['MCP-X-1', $b, 'ppob', 10000, 0, 10000, 'success', 'milik B']);
        try {
            McpRunner::call('get_my_transaction_status', ['reference_id' => 'MCP-X-1'], ['id' => $a]);
            throw new RuntimeException('A bisa baca transaksi B');
        } catch (McpException $e) { assert_true($e->getCode() === -32602); }
    } finally { $pdo->rollBack(); }
});

t('mcp-readonly-no-ledger', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_R','089999000030','_r@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCPR']);
        $uid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,50000,0)')->execute([$uid]);
        $n0 = (int)DB::one('SELECT COUNT(*) c FROM wallet_ledger WHERE user_id = ?', [$uid])['c'];
        McpRunner::call('get_my_balance', [], ['id' => $uid]);
        McpRunner::call('get_my_transaction_history', ['limit' => 5], ['id' => $uid]);
        McpRunner::call('get_my_spending_summary', ['period' => 'month'], ['id' => $uid]);
        $n1 = (int)DB::one('SELECT COUNT(*) c FROM wallet_ledger WHERE user_id = ?', [$uid])['c'];
        assert_true($n0 === $n1, 'read-only menulis ledger');
    } finally { $pdo->rollBack(); }
});

t('mcp-no-cost-leak', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $s = json_encode(McpRunner::call('search_ppob_products', ['limit' => 5], ['id' => 1]));
        assert_true(!preg_match('/base_price|margin|modal/i', $s), 'modal/margin bocor');
    } finally { $pdo->rollBack(); }
});

t('mcp-prepare-needs-policy', function () {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user','_E','089999000040','_e@x.local',?,?,'active')")->execute([password_hash('x', PASSWORD_DEFAULT), 'FPTESTMCPE']);
        $uid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,1000000,0)')->execute([$uid]);
        $r = \App\Services\McpPurchaseService::execute($uid, 'mcp_' . str_repeat('b', 24), '');
        assert_true($r['ok'] === false, 'execute tanpa prepare lolos');
    } finally { $pdo->rollBack(); }
});

echo "\n{$pass} lulus, {$fail} gagal\n";
exit($fail > 0 ? 1 : 0);
