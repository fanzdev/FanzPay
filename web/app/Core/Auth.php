<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\DB;

/**
 * Auth: user saat ini, role, permission granular admin.
 * Role: super_admin | admin | user. TIDAK ada role lain.
 */
final class Auth {
    public const PERMISSIONS = [
        'dashboard.view', 'users.view', 'users.manage',
        'transactions.view', 'transactions.manage',
        'deposits.view', 'deposits.manage',
        'withdrawals.view', 'withdrawals.manage',
        'products.view', 'products.manage',
        'reports.view', 'notifications.manage',
        'admins.manage', 'settings.manage', 'apiconfig.manage',
        'audit.view', 'reconcile.manage',
    ];

    public static function user(): ?array {
        Session::start();
        $id = $_SESSION['uid'] ?? null;
        if (!$id) return null;
        $u = DB::one('SELECT * FROM users WHERE id = ? AND status = ?', [(int)$id, 'active']);
        return $u ?: null;
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function isSuperAdmin(?array $u = null): bool {
        $u = $u ?? self::user();
        return $u !== null && $u['role'] === 'super_admin';
    }

    public static function isAdmin(?array $u = null): bool {
        $u = $u ?? self::user();
        return $u !== null && in_array($u['role'], ['admin', 'super_admin'], true);
    }

    public static function can(string $perm, ?array $u = null): bool {
        $u = $u ?? self::user();
        if (!$u) return false;
        if ($u['role'] === 'super_admin') return true;
        if ($u['role'] === 'user') return false;
        $row = DB::one('SELECT 1 FROM admin_permissions WHERE admin_id = ? AND permission = ?', [(int)$u['id'], $perm]);
        return $row !== null;
    }

    public static function login(array $user): void {
        Session::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $_SESSION['_created'] = time();
        DB::exec('INSERT INTO user_sessions (user_id, session_id, ip, user_agent, created_at, expires_at) VALUES (?,?,?,?,NOW(), DATE_ADD(NOW(), INTERVAL 12 HOUR))', [(int)$user['id'], session_id(), client_ip(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    }

    public static function logout(): void {
        Session::start();
        $sid = session_id();
        try { DB::exec('DELETE FROM user_sessions WHERE session_id = ?', [$sid]); } catch (\Throwable $e) {}
        Session::destroy();
    }

    /** Auth untuk JSON API via Bearer token. */
    public static function userFromBearer(): ?array {
        $h = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($h === '' && function_exists('apache_request_headers')) {
            $heads = apache_request_headers() ?: [];
            foreach ($heads as $k => $v) {
                if (strtolower((string)$k) === 'authorization') { $h = (string)$v; break; }
            }
        }
        if (!str_starts_with($h, 'Bearer ')) return null;
        $token = substr($h, 7);
        if (strlen($token) < 20) return null;
        $hash = hash('sha256', $token);
        $row = DB::one('SELECT t.*, u.* FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = ?', [$hash, 'active']);
        if (!$row) return null;
        return $row;
    }
}
