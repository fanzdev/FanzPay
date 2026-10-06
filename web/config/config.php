<?php
declare(strict_types=1);

/**
 * FanzPay configuration loader.
 * Membaca web/.env (format KEY=value sederhana). Kredensial provider
 * HANYA diakses dari sisi server melalui config('buatqris.*') / config('digiflazz.*').
 */

if (!function_exists('fp_load_env')) {
function fp_load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if (strlen($val) >= 2 && (($val[0] === '"' && substr($val, -1) === '"') || ($val[0] === "'" && substr($val, -1) === "'"))) {
            $val = substr($val, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
}
} // fp_load_env guard

if (!function_exists('env')) {
function env(string $key, $default = null) {
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return $default;
    return $v;
}
}

fp_load_env(dirname(__DIR__) . '/.env');

if (!function_exists('fp_base_path')) {
function fp_base_path(string $p = ''): string {
    $base = dirname(__DIR__);
    return $p === '' ? $base : $base . '/' . ltrim($p, '/');
}
}

/** Deteksi base URL + base path (mendukung subfolder XAMPP & root cPanel). */
if (!function_exists('fp_detect_base')) {
function fp_detect_base(): array {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(str_replace('/index.php', '', $script), '/');
    // index.php selalu di web/, jadi base path = direktori script
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = $dir === '' ? '' : $dir;
    $envUrl = (string)env('APP_URL', '');
    if ($envUrl !== '' && PHP_SAPI !== 'cli') {
        $parts = parse_url($envUrl);
        if (!empty($parts['path'])) $basePath = rtrim($parts['path'], '/');
    }
    return ['url' => $scheme . '://' . $host . $basePath, 'path' => $basePath];
}
}

return [
    'app' => [
        'name'  => env('APP_NAME', 'FanzPay'),
        'env'   => env('APP_ENV', 'local'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'url'   => env('APP_URL', 'http://localhost/fanzpay/web'),
        'key'   => env('APP_KEY', 'change-me'),
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int)env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'fanzpay'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
    ],
    'buatqris' => [
        'account_id'     => env('BUATQRIS_ACCOUNT_ID', ''),
        'secret_token'   => env('BUATQRIS_SECRET_TOKEN', ''),
        'signing_secret' => env('BUATQRIS_SIGNING_SECRET', ''),
        'base_url'       => env('BUATQRIS_BASE_URL', 'https://api.buatqris.site'),
        'sandbox'        => filter_var(env('BUATQRIS_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'digiflazz' => [
        'username'  => env('DIGIFLAZZ_USERNAME', ''),
        'api_key'   => env('DIGIFLAZZ_API_KEY', ''),
        'base_url'  => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1'),
        'sandbox'   => filter_var(env('DIGIFLAZZ_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'mail' => [
        'from'      => env('MAIL_FROM', 'noreply@fanzpay.local'),
        'from_name' => env('MAIL_FROM_NAME', 'FanzPay'),
    ],
];
