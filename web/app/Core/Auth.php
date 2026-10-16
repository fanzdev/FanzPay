<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\DB;

/**
 * Auth: user saat ini, role, permission granular admin.
 * Role: super_admin | admin | user. TIDAK ada role lain.
 */
final class Auth {
    public const REMEMBER_COOKIE = 'fp_remember';
    public const REMEMBER_LIFETIME = 2592000; // 30 hari (detik) — sesi tersimpan di local/komputer
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
        if ($id) {
            $u = DB::one('SELECT * FROM users WHERE id = ? AND status = ?', [(int)$id, 'active']);
            if ($u) return $u;
        }
        // Fallback: login otomatis via cookie "ingat saya" yang tersimpan di local/komputer.
        $remembered = self::tryRememberLogin();
        if ($remembered) return $remembered;
        if (!$id) return null;
        return null;
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

    public static function login(array $user, bool $remember = true): void {
        Session::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $_SESSION['_created'] = time();
        DB::exec('INSERT INTO user_sessions (user_id, session_id, ip, user_agent, created_at, expires_at) VALUES (?,?,?,?,NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))', [(int)$user['id'], session_id(), client_ip(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        if ($remember) {
            self::issueRemember((int)$user['id']);
        } else {
            self::clearRememberCookieOnly();
        }
    }

    public static function logout(): void {
        Session::start();
        $sid = session_id();
        try { DB::exec('DELETE FROM user_sessions WHERE session_id = ?', [$sid]); } catch (\Throwable $e) {}
        self::clearRemember();
        Session::destroy();
    }

    /**
     * Terbitkan cookie "ingat saya" berumur 30 hari.
     * Token mentah hanya disimpan di browser; di DB hanya hash SHA-256.
     */
    public static function issueRemember(int $userId): void {
        try { self::ensureRememberTable(); } catch (\Throwable $e) { return; }
        try {
            // Bersihkan yang kedaluwarsa + batasi max 10 perangkat per user.
            DB::exec('DELETE FROM remember_tokens WHERE expires_at <= NOW()');
            $count = DB::one('SELECT COUNT(*) c FROM remember_tokens WHERE user_id = ?', [$userId]);
            if ($count && (int)$count['c'] >= 10) {
                DB::exec('DELETE FROM remember_tokens WHERE user_id = ? ORDER BY created_at ASC LIMIT 1', [$userId]);
            }
            $token = bin2hex(random_bytes(32)); // 64 hex char
            $hash = hash('sha256', $token);
            DB::exec(
                'INSERT INTO remember_tokens (user_id, token_hash, ip, user_agent, created_at, expires_at) VALUES (?,?,?, ?,NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))',
                [$userId, $hash, client_ip(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]
            );
            self::setRememberCookie($userId . ':' . $token, time() + self::REMEMBER_LIFETIME);
        } catch (\Throwable $e) {}
    }

    /**
     * Coba login otomatis dari cookie. Dipanggil oleh user().
     * Berhasil → sesi dipulihkan + token dirotasi. Gagal → null.
     */
    public static function tryRememberLogin(): ?array {
        if (!empty($_SESSION['uid'])) return null;
        $raw = (string)($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if ($raw === '' || !str_contains($raw, ':')) return null;
        [$uidStr, $token] = explode(':', $raw, 2);
        $uid = (int)$uidStr;
        if ($uid <= 0 || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            self::clearRememberCookieOnly();
            return null;
        }
        try {
            self::ensureRememberTable();
            $hash = hash('sha256', $token);
            $row = DB::one('SELECT * FROM remember_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1', [$uid, $hash]);
            if (!$row) {
                self::clearRememberCookieOnly();
                return null;
            }
            $u = DB::one('SELECT * FROM users WHERE id = ? AND status = ?', [$uid, 'active']);
            if (!$u) {
                DB::exec('DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?', [$uid, $hash]);
                self::clearRememberCookieOnly();
                return null;
            }
            // Rotasi token: hapus token lama, terbitkan baru (anti replay).
            DB::exec('DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?', [$uid, $hash]);
            Session::start();
            if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['_created'])) {
                $_SESSION['_created'] = time();
            }
            $_SESSION['uid'] = (int)$u['id'];
            DB::exec('INSERT INTO user_sessions (user_id, session_id, ip, user_agent, created_at, expires_at) VALUES (?,?,?,?,NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))', [(int)$u['id'], session_id(), client_ip(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
            self::issueRemember((int)$u['id']);
            return $u;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Hapus token di DB + hapus cookie di browser. */
    public static function clearRemember(): void {
        $raw = (string)($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if ($raw !== '' && str_contains($raw, ':')) {
            [$uidStr, $token] = explode(':', $raw, 2);
            if (preg_match('/^[a-f0-9]{64}$/', (string)$token)) {
                try {
                    self::ensureRememberTable();
                    DB::exec('DELETE FROM remember_tokens WHERE token_hash = ?', [hash('sha256', (string)$token)]);
                } catch (\Throwable $e) {}
            }
        }
        self::clearRememberCookieOnly();
    }

    private static function clearRememberCookieOnly(): void {
        unset($_COOKIE[self::REMEMBER_COOKIE]);
        if (!headers_sent()) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie(self::REMEMBER_COOKIE, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => $secure,
                'samesite' => 'Lax',
            ]);
        }
    }

    private static function setRememberCookie(string $value, int $expires): void {
        $_COOKIE[self::REMEMBER_COOKIE] = $value;
        if (!headers_sent()) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie(self::REMEMBER_COOKIE, $value, [
                'expires' => $expires,
                'path' => '/',
                'httponly' => true,
                'secure' => $secure,
                'samesite' => 'Lax',
            ]);
        }
    }

    /** Buat tabel otomatis bila belum ada (agar install lama tidak perlu import manual). */
    private static function ensureRememberTable(): void {
        DB::exec("CREATE TABLE IF NOT EXISTS remember_tokens (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED NOT NULL,
          token_hash CHAR(64) NOT NULL,
          ip VARCHAR(64) NOT NULL DEFAULT '',
          user_agent VARCHAR(255) NOT NULL DEFAULT '',
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          expires_at DATETIME NOT NULL,
          UNIQUE KEY uq_remember (token_hash),
          KEY idx_remember_user (user_id),
          CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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
