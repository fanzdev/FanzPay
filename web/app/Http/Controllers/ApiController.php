<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\DepositService;
use App\Services\NotificationService;
use App\Services\PinService;
use App\Services\PpobService;
use App\Services\PricingService;
use App\Services\TransferService;
use App\Services\WalletService;
use App\Services\WithdrawService;

/**
 * JSON API internal untuk frontend web (session) + aplikasi Android (Bearer).
 * TIDAK PERNAH mengembalikan secret/modal/margin.
 */
final class ApiController {
    private ?array $u;

    public function __construct() {
        $this->u = Auth::user() ?? Auth::userFromBearer();
    }

    private function needAuth(): array {
        if (!$this->u) json_response(['ok' => false, 'message' => 'Unauthorized.'], 401);
        return $this->u;
    }

    /**
     * Aplikasi Android KHUSUS role user/pelanggan.
     * Admin & super_admin wajib lewat web (lebih aman + butuh permission granular).
     */
    private function needUser(): array {
        $u = $this->needAuth();
        if (($u['role'] ?? '') !== 'user') {
            json_response(['ok' => false, 'message' => 'Akun admin wajib masuk lewat website. Buka ' . url('login') . ' di browser. Aplikasi Android khusus pelanggan.'], 403);
        }
        if (($u['status'] ?? 'active') !== 'active') json_response(['ok' => false, 'message' => 'Akun belum aktif.'], 403);
        return $u;
    }

    // POST /api/auth/login {email,password} → {token}
    // KHUSUS pelanggan. Admin/super_admin ditolak dengan pesan yang jelas.
    public function login(): void {
        $in = json_input();
        $rl = RateLimiter::hit('api-login:' . client_ip(), 10, 300);
        if (!$rl['allowed']) json_response(['ok' => false, 'message' => 'Terlalu banyak percobaan.'], 429);
        $u = DB::one('SELECT * FROM users WHERE email = ?', [trim((string)($in['email'] ?? ''))]);
        if (!$u || !password_verify((string)($in['password'] ?? ''), (string)$u['password_hash']) || $u['status'] !== 'active') {
            json_response(['ok' => false, 'message' => 'Email atau kata sandi salah.'], 401);
        }
        if (($u['role'] ?? 'user') !== 'user') {
            Logger::audit((int)$u['id'], $u['role'], 'api.login.blocked_role', 'users', (int)$u['id'], 'fail', ['device' => substr((string)($in['device'] ?? 'android'), 0, 60)]);
            json_response(['ok' => false, 'message' => 'Akun admin & super admin hanya bisa masuk lewat website. Aplikasi Android khusus pelanggan.'], 403);
        }
        $token = bin2hex(random_bytes(32));
        DB::exec('INSERT INTO api_tokens (user_id, token_hash, device, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 30 DAY))', [(int)$u['id'], hash('sha256', $token), substr((string)($in['device'] ?? 'android'), 0, 120)]);
        Logger::audit((int)$u['id'], $u['role'], 'api.login', 'users', (int)$u['id']);
        json_response(['ok' => true, 'data' => [
            'token' => $token, 'user' => ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'phone' => $u['phone'], 'role' => $u['role'], 'public_id' => $u['public_id']],
        ]]);
    }

    public function me(): void {
        $u = $this->needAuth();
        json_response(['ok' => true, 'data' => [
            'user' => ['name' => $u['name'], 'email' => $u['email'], 'phone' => $u['phone'], 'role' => $u['role'], 'public_id' => $u['public_id']],
            'balance' => WalletService::balance((int)$u['id']),
            'has_pin' => PinService::has((int)$u['id']),
            'unread' => NotificationService::unreadCount((int)$u['id']),
        ]]);
    }

    public function wallet(): void {
        $u = $this->needUser();
        $uid = (int)$u['id'];
        $type = (string)($_GET['type'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $rows = WalletService::history($uid, in_array($type, ['deposit', 'withdraw', 'transfer_in', 'transfer_out', 'ppob', 'refund'], true) ? $type : '', 20, ($page - 1) * 20);
        json_response(['ok' => true, 'data' => ['balance' => WalletService::balance($uid), 'ledger' => $rows, 'page' => $page]]);
    }

    public function products(): void {
        $items = PricingService::catalog(isset($_GET['cat']) && $_GET['cat'] !== '' ? (string)$_GET['cat'] : null);
        json_response(['ok' => true, 'data' => $items]);
    }

    public function resolve(): void {
        $this->needAuth();
        $q = trim((string)($_GET['q'] ?? ''));
        $phone = Validator::normalizePhone(preg_replace('/^fanzpay:\/\/pay\/u\//i', '', $q));
        $t = DB::one('SELECT name, phone, public_id FROM users WHERE (phone = ? OR public_id = ?) AND role="user" AND status="active"', [$phone, $q]);
        if (!$t) json_response(['ok' => false, 'message' => 'Penerima tidak ditemukan.'], 404);
        json_response(['ok' => true, 'data' => ['name' => $t['name'], 'phone' => mask_phone($t['phone']), 'public_id' => $t['public_id']]]);
    }

    public function transfer(): void {
        $u = $this->needUser();
        $in = json_input();
        $r = TransferService::transfer((int)$u['id'], (string)($in['lookup'] ?? ''), (int)($in['amount'] ?? 0), (string)($in['pin'] ?? ''), (string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ''));
        if (!$r['ok']) json_response(['ok' => false, 'message' => $r['message']], 422);
        json_response(['ok' => true, 'data' => $r['transfer']]);
    }

    public function deposit(): void {
        $u = $this->needUser();
        $in = json_input();
        $rl = RateLimiter::hit('api-deposit:' . (int)$u['id'], 10, 3600);
        if (!$rl['allowed']) json_response(['ok' => false, 'message' => 'Terlalu sering. Coba lagi nanti.'], 429);
        $r = DepositService::create((int)$u['id'], (int)($in['amount'] ?? 0), (string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ''), strtolower(trim((string)($in['method'] ?? 'qris'))));
        if (!$r['ok']) json_response(['ok' => false, 'message' => $r['message']], 422);
        json_response(['ok' => true, 'data' => $r['deposit']]);
    }

    public function depositStatus(string $ref): void {
        $u = $this->needUser();
        $d = DB::one('SELECT reference_id, status, amount, fee, total_amount, payment_type, payment_code, qr_url, payment_url, expired_at, paid_at FROM deposits WHERE reference_id=? AND user_id=?', [$ref, (int)$u['id']]);
        if (!$d) json_response(['ok' => false, 'message' => 'Tidak ditemukan.'], 404);
        json_response(['ok' => true, 'data' => $d]);
    }

    public function withdraw(): void {
        $u = $this->needUser();
        $in = json_input();
        $r = WithdrawService::request((int)$u['id'], (int)($in['destination_id'] ?? 0), (int)($in['amount'] ?? 0), (string)($in['pin'] ?? ''), (string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ''));
        if (!$r['ok']) json_response(['ok' => false, 'message' => $r['message']], 422);
        json_response(['ok' => true, 'data' => $r['withdrawal']]);
    }

    public function destinations(): void {
        $u = $this->needUser();
        json_response(['ok' => true, 'data' => DB::all('SELECT * FROM withdrawal_destinations WHERE user_id=? ORDER BY id DESC', [(int)$u['id']])]);
    }

    public function ppob(): void {
        $u = $this->needUser();
        $in = json_input();
        $r = PpobService::purchase((int)$u['id'], (string)($in['code'] ?? ''), (string)($in['customer_no'] ?? ''), (string)($in['pin'] ?? ''), (string)($_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? ''));
        if (!$r['ok']) json_response(['ok' => false, 'message' => $r['message']], 422);
        json_response(['ok' => true, 'data' => $r['transaction']]);
    }

    public function transactions(): void {
        $u = $this->needUser();
        $rows = DB::all('SELECT reference_id, kind, amount, total, status, note, created_at FROM transactions WHERE user_id=? ORDER BY id DESC LIMIT 30', [(int)$u['id']]);
        json_response(['ok' => true, 'data' => $rows]);
    }

    public function notifications(): void {
        $u = $this->needUser();
        $rows = NotificationService::list((int)$u['id'], 30);
        DB::exec('UPDATE notifications SET is_read=1 WHERE user_id=?', [(int)$u['id']]);
        json_response(['ok' => true, 'data' => $rows]);
    }

    public function setPin(): void {
        $u = $this->needUser();
        $in = json_input();
        if (!preg_match('/^\d{6}$/', (string)($in['pin'] ?? ''))) json_response(['ok' => false, 'message' => 'PIN harus 6 digit.'], 422);
        PinService::set((int)$u['id'], (string)$in['pin']);
        json_response(['ok' => true]);
    }
}

function json_input(): array {
    $raw = (string)file_get_contents('php://input');
    $raw = ltrim($raw, "\xEF\xBB\xBF \t\n\r");
    $d = json_decode($raw, true);
    if (is_array($d)) return $d;
    return $_POST;
}

function mask_phone(string $p): string {
    $len = strlen($p);
    if ($len < 6) return $p;
    return substr($p, 0, 4) . str_repeat('•', $len - 7) . substr($p, -3);
}
