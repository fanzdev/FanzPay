<?php
declare(strict_types=1);

/** Global helpers: escaping, rupiah, id, url, session flash. */

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rupiah(int $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function config(string $key, $default = null) {
    static $cfg = null;
    if ($cfg === null) $cfg = require dirname(__DIR__, 2) . '/config/config.php';
    $parts = explode('.', $key);
    $v = $cfg;
    foreach ($parts as $p) {
        if (!is_array($v) || !array_key_exists($p, $v)) return $default;
        $v = $v[$p];
    }
    return $v;
}

function app_base(): array {
    static $b = null;
    if ($b === null) $b = fp_detect_base();
    return $b;
}

function url(string $path = ''): string {
    $b = app_base();
    return $b['url'] . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    $v = '1.0.0';
    $file = fp_base_path('public/assets/' . ltrim($path, '/'));
    if (is_file($file)) $v = (string)filemtime($file);
    return url('public/assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** Reference ID unik — TIDAK memakai timestamp saja. */
function new_reference(string $prefix): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(6))) . '-' . base_convert((string)(microtime(true) * 1000), 10, 36);
}

/** Public identifier opaque untuk QR user. */
function new_public_id(): string {
    return 'FP' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
}

function client_ip(): string {
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = explode(',', (string)$_SERVER[$k])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

function flash(string $key, $value = null) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if ($value === null) {
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
    $_SESSION['_flash'][$key] = $value;
}

function old_input(string $key, string $default = ''): string {
    $v = flash('old_' . $key);
    return $v !== null ? (string)$v : $default;
}

/** Masking rahasia untuk log/tampilan admin. */
function mask_secret(?string $s, int $visible = 4): string {
    $s = (string)$s;
    if ($s === '') return '— belum diatur —';
    $len = strlen($s);
    if ($len <= $visible + 3) return '••••••••';
    return substr($s, 0, 3) . '_•••' . substr($s, -$visible);
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path, int $code = 302): void {
    header('Location: ' . url($path), true, $code);
    exit;
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
