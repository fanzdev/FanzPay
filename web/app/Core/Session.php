<?php
declare(strict_types=1);

namespace App\Core;

/** Session hardening: regenerate, expiry, invalidation.
 * Cookie dibuat persisten 30 hari agar user tetap login setelah
 * browser ditutup / komputer di-restart (disimpan di local/komputer).
 */
final class Session {
    public const LIFETIME = 2592000; // 30 hari (detik)

    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        // Sinkronkan masa berlaku session file di server dengan cookie.
        @ini_set('session.gc_maxlifetime', (string)self::LIFETIME);
        @ini_set('session.cookie_lifetime', (string)self::LIFETIME);
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path' => '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
        ]);
        session_start();
        $now = time();
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = $now;
            session_regenerate_id(true);
        } elseif ($now - (int)$_SESSION['_created'] > self::LIFETIME) {
            self::destroy();
            session_start();
            $_SESSION['_created'] = $now;
        } elseif ($now - (int)($_SESSION['_touched'] ?? $now) > 900) {
            $_SESSION['_touched'] = $now;
            session_regenerate_id(true);
        }
        $_SESSION['_touched'] = $now;
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $p['path'] ?? '/',
                'httponly' => (bool)($p['httponly'] ?? true),
                'secure' => (bool)($p['secure'] ?? false),
                'samesite' => 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }
}
