<?php
declare(strict_types=1);

namespace App\Core;

/** CSRF token per-session. */
final class CSRF {
    public static function token(): string {
        Session::start();
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function check(?string $token): bool {
        Session::start();
        $ok = is_string($token) && hash_equals((string)($_SESSION['_csrf'] ?? ''), $token);
        return $ok;
    }
}
