<?php
declare(strict_types=1);

namespace App\Core;

/** Session hardening: regenerate, expiry, invalidation. */
final class Session {
    private const LIFETIME = 43200; // 12 jam

    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
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
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], '', (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
    }
}
