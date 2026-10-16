<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\DB;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\View;
use App\Mcp\McpAuth;
use App\Mcp\McpServer;
use App\Mcp\McpTools;
use App\Services\McpPolicyService;
use App\Services\NotificationService;
use App\Services\Settings;
use App\Services\WalletService;

/**
 * MCP Server (JSON-RPC via HTTPS) + halaman akun pelanggan + setup.
 * Protokol: POST /mcp. Discovery: GET /mcp.
 */
final class McpController {
    // ---- Protokol ----

    /** GET /mcp — discovery manusia-mesin. */
    public function discovery(): void {
        header('Content-Type: application/json; charset=utf-8');
        $tools = [];
        foreach (McpTools::definitions() as $name => $def) {
            if (Settings::get('mcp_tool_' . $name, '1') !== '1') continue;
            $tools[] = $name;
        }
        echo json_encode([
            'name' => McpServer::SERVER, 'version' => McpServer::SERVER_VERSION,
            'protocol' => McpServer::VERSION, 'transport' => 'streamable-http',
            'endpoint' => url('mcp'), 'auth' => 'bearer-mcp-token',
            'setup' => url('panduan-mcp'), 'enabled' => Settings::get('mcp_enabled', '1') === '1',
            'tools' => $tools,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** POST /mcp — JSON-RPC 2.0. Mendukung single + batch (maks 10). */
    public function endpoint(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { $this->discovery(); }
        if (Settings::get('mcp_enabled', '1') !== '1') {
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32603, 'message' => 'MCP Server nonaktif.']]);
            exit;
        }
        $raw = (string)file_get_contents('php://input');
        if (strlen($raw) > 65536) {
            http_response_code(413);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32602, 'message' => 'Body terlalu besar (maks 64 KB).']]);
            exit;
        }
        $decoded = json_decode(ltrim($raw, "\xEF\xBB\xBF \t\n\r"), true);
        header('Content-Type: application/json; charset=utf-8');
        $auth = McpAuth::fromRequest();
        if (is_array($decoded) && array_is_list($decoded)) {
            $out = [];
            foreach (array_slice($decoded, 0, 10) as $r) $out[] = McpServer::dispatch(is_array($r) ? $r : [], $auth);
            echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!is_array($decoded)) {
            echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Body bukan JSON valid.']]);
            exit;
        }
        echo json_encode(McpServer::dispatch($decoded, $auth), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- Halaman pelanggan (login web resmi) ----

    private function needUser(): array {
        $u = Auth::user();
        if (!$u) redirect('login');
        if ($u['role'] !== 'user') redirect(panel_home($u));
        return $u;
    }

    /** GET /mcp-koneksi — kelola token + lihat kebijakan. */
    public function connections(): void {
        $u = $this->needUser();
        $uid = (int)$u['id'];
        McpAuth::ensureTables();
        $tokens = [];
        try {
            $tokens = DB::all('SELECT id, name, client, scopes, expires_at, revoked_at, last_used_at, created_at FROM mcp_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$uid]);
        } catch (\Throwable $e) {}
        $policy = McpPolicyService::forUser($uid);
        $balance = WalletService::balance($uid);
        $flashToken = flash('mcp_new_token');
        View::render('user/mcp', [
            'title' => 'Koneksi AI (MCP)', 'nav' => 'mcp', 'user' => $u, 'balance' => $balance,
            'unread' => NotificationService::unreadCount($uid), 'has_pin' => \App\Services\PinService::has($uid),
            'tokens' => $tokens, 'policy' => $policy, 'new_token' => $flashToken,
            'endpoint' => url('mcp'), 'ok' => flash('ok'), 'error' => flash('error'),
        ], 'app');
    }

    /** POST /mcp-koneksi/buat — terbitkan token (tampil sekali). */
    public function createToken(): void {
        $u = $this->needUser();
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('mcp-koneksi'); }
        $rl = RateLimiter::hit('mcp-issue:' . (int)$u['id'], 5, 3600);
        if (!$rl['allowed']) { flash('error', 'Terlalu sering membuat token.'); redirect('mcp-koneksi'); }
        $name = mb_substr(trim((string)($_POST['name'] ?? 'AI connector')), 0, 80);
        $client = mb_substr(trim((string)($_POST['client'] ?? 'generic')), 0, 80);
        $scopes = !empty($_POST['allow_purchase']) ? ['read', 'purchase'] : ['read'];
        if ($name === '') $name = 'AI connector';
        $token = McpAuth::issue((int)$u['id'], $name, $client, $scopes);
        NotificationService::send((int)$u['id'], 'mcp', 'Koneksi AI baru', $name . ' (' . implode(',', $scopes) . ') terhubung.');
        Logger::audit((int)$u['id'], 'user', 'mcp.token.issue', 'mcp_tokens', null);
        flash('mcp_new_token', $token);
        flash('ok', 'Token dibuat. Salin sekarang — hanya tampil sekali.');
        redirect('mcp-koneksi');
    }

    /** POST /mcp-koneksi/{id}/cabut */
    public function revokeToken(string $id): void {
        $u = $this->needUser();
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('mcp-koneksi');
        McpAuth::revoke((int)$u['id'], (int)$id);
        NotificationService::send((int)$u['id'], 'mcp', 'Akses AI dicabut', 'Token #' . (int)$id . ' dicabut.');
        Logger::audit((int)$u['id'], 'user', 'mcp.token.revoke', 'mcp_tokens', (int)$id);
        flash('ok', 'Token dicabut.');
        redirect('mcp-koneksi');
    }

    /** GET /mcp-kebijakan — form kebijakan pembelian otomatis. */
    public function policy(): void {
        $u = $this->needUser();
        $uid = (int)$u['id'];
        $policy = McpPolicyService::forUser($uid);
        try {
            $cats = DB::all('SELECT code, name FROM product_categories WHERE is_active=1 ORDER BY sort ASC');
        } catch (\Throwable $e) { $cats = []; }
        View::render('user/mcp-policy', [
            'title' => 'Kebijakan Pembelian AI', 'nav' => 'mcp', 'user' => $u,
            'balance' => WalletService::balance($uid), 'unread' => NotificationService::unreadCount($uid),
            'has_pin' => \App\Services\PinService::has($uid),
            'policy' => $policy, 'cats' => $cats, 'ok' => flash('ok'), 'error' => flash('error'),
        ], 'app');
    }

    /** POST /mcp-kebijakan — simpan (opt-in eksplisit + risiko). */
    public function savePolicy(): void {
        $u = $this->needUser();
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('mcp-kebijakan'); }
        $enabled = !empty($_POST['enabled']) && !empty($_POST['agree_risk']);
        if (!empty($_POST['enabled']) && empty($_POST['agree_risk'])) {
            flash('error', 'Centang persetujuan risiko untuk mengaktifkan.');
            redirect('mcp-kebijakan');
        }
        $cats = is_array($_POST['cats'] ?? null) ? array_map('strtolower', $_POST['cats']) : [];
        McpPolicyService::save(
            (int)$u['id'], $enabled,
            max(0, (int)($_POST['max_per_tx'] ?? 0)), max(0, (int)($_POST['max_daily'] ?? 0)),
            $cats, !empty($_POST['allow_new_numbers'])
        );
        NotificationService::send((int)$u['id'], 'mcp', 'Kebijakan AI diubah', $enabled ? 'Pembelian otomatis AKTIF.' : 'Pembelian otomatis nonaktif.');
        Logger::audit((int)$u['id'], 'user', 'mcp.policy.update', 'mcp_purchase_policy', (int)$u['id'], 'ok', ['enabled' => $enabled]);
        flash('ok', 'Kebijakan disimpan.');
        redirect('mcp-kebijakan');
    }
}
