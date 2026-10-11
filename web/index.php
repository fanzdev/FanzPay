<?php
declare(strict_types=1);

/**
 * FanzPay front controller. Semua request web/API/webhook lewat sini.
 * cPanel-friendly: cukup arahkan document root ke folder web/.
 */

require __DIR__ . '/config/config.php';
require __DIR__ . '/app/Core/helpers.php';

// PHP built-in server: layani file statis langsung (Apache sudah via .htaccess).
if (PHP_SAPI === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $static = __DIR__ . $reqPath;
    if ($reqPath !== '/' && is_file($static) && str_starts_with(realpath($static) ?: '', realpath(__DIR__) ?: __DIR__)) {
        $ext = strtolower(pathinfo($static, PATHINFO_EXTENSION));
        $mime = ['css' => 'text/css', 'js' => 'text/javascript', 'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'xml' => 'application/xml', 'txt' => 'text/plain', 'apk' => 'application/vnd.android.package-archive'][$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=3600');
        readfile($static);
        exit;
    }
}

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Services\Settings;

require_once __DIR__ . '/views/partials/icons.php';

Session::start();

// ---- security headers ----
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ---- maintenance mode (super admin tetap bisa masuk) ----
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = app_base()['path'];
if ($basePath !== '' && str_starts_with($path, $basePath)) $path = substr($path, strlen($basePath));
$path = '/' . trim($path, '/');
if ($path === '/index.php') $path = '/';

$isApi = str_starts_with($path, '/api/');
$isWebhook = str_starts_with($path, '/webhooks/');

if (!$isApi && !$isWebhook && Settings::maintenance()) {
    $u = Auth::user();
    $allowed = $u && $u['role'] === 'super_admin' && in_array($path, ['/login', '/logout', '/admin', '/admin/pengaturan'], true);
    // setup-super selalu boleh diakses saat maintenance (dibatasi sendiri: hanya saat belum ada super_admin).
    // Halaman publik esensial tetap boleh dibaca agar SEO & unduhan APK tidak mati.
    $publicOk = in_array($path, ['/', '/login', '/register', '/verify', '/setup-super', '/download-apk', '/unduh', '/sitemap.xml', '/robots.txt'], true)
        || str_starts_with($path, '/pay/') || str_starts_with($path, '/public/assets');
    if (!$allowed && !$publicOk) {
        http_response_code(503);
        View::render('errors/maintenance', ['title' => 'Maintenance'], 'public');
        exit;
    }
}

// ---- error handling: user-friendly, detail hanya ke log ----
set_exception_handler(function (Throwable $e): void {
    Logger::log('exception', ['msg' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
    $acceptsJson = str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'json') || str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', app_base()['path'] . '/api');
    if ($acceptsJson) { json_response(['ok' => false, 'message' => 'Transaksi sedang diproses.'], 500); }
    http_response_code(500);
    try { View::render('errors/500', ['title' => 'Gangguan'], 'public'); } catch (Throwable $t) { echo 'Transaksi sedang diproses.'; }
});

$router = new Router();

// Publik
$router->get('/', ['WebController', 'landing']);
$router->get('/layanan', ['WebController', 'layanan']);
$router->get('/cara-kerja', ['WebController', 'caraKerja']);
$router->get('/keunggulan', ['WebController', 'keunggulan']);
$router->get('/harga', ['WebController', 'harga']);
$router->get('/faq', ['WebController', 'faq']);
$router->get('/bantuan', ['WebController', 'bantuan']);
$router->get('/syarat-ketentuan', ['WebController', 'syarat']);
$router->get('/kebijakan-privasi', ['WebController', 'privasi']);
$router->get('/kebijakan-refund', ['WebController', 'refund']);
$router->get('/pay/{id}', ['WebController', 'pay']);
$router->get('/login', ['AuthController', 'login']);
$router->post('/login', ['AuthController', 'doLogin']);
$router->get('/register', ['AuthController', 'register']);
$router->post('/register', ['AuthController', 'doRegister']);
$router->get('/verify', ['AuthController', 'verify']);
$router->post('/verify', ['AuthController', 'doVerify']);
$router->get('/logout', ['AuthController', 'logout']);
// Pendaftaran Super Admin pertama — hanya bisa dipakai saat belum ada super_admin.
// Setelah satu super_admin ada, rute ini otomatis terkunci (redirect ke login).
$router->get('/setup-super', ['AuthController', 'setupSuper']);
$router->post('/setup-super', ['AuthController', 'doSetupSuper']);
// Unduhan APK Android + SEO
$router->get('/download-apk', ['WebController', 'downloadApk']);
$router->get('/unduh', ['WebController', 'downloadApk']);
$router->get('/sitemap.xml', ['WebController', 'sitemap']);
$router->get('/robots.txt', ['WebController', 'robots']);

// User
$router->get('/home', ['UserController', 'home']);
$router->get('/produk', ['UserController', 'products']);
$router->get('/produk/{code}', ['UserController', 'productDetail']);
$router->post('/ppob/beli', ['UserController', 'doPpob']);
$router->get('/transfer', ['UserController', 'transfer']);
$router->post('/transfer', ['UserController', 'doTransfer']);
$router->get('/deposit', ['UserController', 'deposit']);
$router->post('/deposit', ['UserController', 'doDeposit']);
$router->get('/deposit/{ref}', ['UserController', 'depositShow']);
$router->get('/withdraw', ['UserController', 'withdraw']);
$router->post('/withdraw/tujuan', ['UserController', 'doWithdrawDest']);
$router->post('/withdraw', ['UserController', 'doWithdraw']);
$router->get('/riwayat', ['UserController', 'history']);
$router->get('/riwayat/{ref}', ['UserController', 'txDetail']);
$router->get('/notifikasi', ['UserController', 'notifications']);
$router->get('/profil', ['UserController', 'profile']);
$router->post('/profil', ['UserController', 'doProfile']);
$router->get('/pin', ['UserController', 'pin']);
$router->post('/pin', ['UserController', 'doPin']);
$router->get('/qr', ['UserController', 'qr']);
// alias root untuk user
$router->get('/dashboard', ['UserController', 'home']);

// Admin
$router->get('/admin', ['AdminController', 'dashboard']);
$router->get('/admin/pengguna', ['AdminController', 'users']);
$router->post('/admin/pengguna/{id}/toggle', ['AdminController', 'userToggle']);
$router->get('/admin/admin', ['AdminController', 'admins']);
$router->post('/admin/admin/buat', ['AdminController', 'adminStore']);
$router->post('/admin/admin/{id}/perms', ['AdminController', 'adminPerms']);
$router->get('/admin/transaksi', ['AdminController', 'transactions']);
$router->get('/admin/deposit', ['AdminController', 'deposits']);
$router->get('/admin/withdraw', ['AdminController', 'withdrawals']);
$router->post('/admin/withdraw/{ref}/reject', ['AdminController', 'withdrawalReject']);
$router->get('/admin/produk', ['AdminController', 'products']);
$router->post('/admin/produk/harga', ['AdminController', 'priceSave']);
$router->post('/admin/produk/{id}/toggle', ['AdminController', 'productToggle']);
$router->post('/admin/produk/sinkron', ['AdminController', 'syncProducts']);
$router->get('/admin/laporan', ['AdminController', 'reports']);
$router->get('/admin/audit', ['AdminController', 'audit']);
$router->get('/admin/rekonsiliasi', ['AdminController', 'reconcile']);
$router->post('/admin/rekonsiliasi/{ref}/refund', ['AdminController', 'reconcileRefund']);
$router->get('/admin/notifikasi', ['AdminController', 'notifications']);
$router->post('/admin/notifikasi', ['AdminController', 'notifications']);
$router->get('/admin/pengaturan', ['AdminController', 'settings']);
$router->post('/admin/pengaturan', ['AdminController', 'settings']);
$router->get('/admin/api', ['AdminController', 'apiConfig']);
$router->post('/admin/api', ['AdminController', 'apiConfig']);

// API JSON
$router->post('/api/auth/login', ['ApiController', 'login']);
$router->get('/api/me', ['ApiController', 'me']);
$router->get('/api/wallet', ['ApiController', 'wallet']);
$router->get('/api/products', ['ApiController', 'products']);
$router->get('/api/resolve', ['ApiController', 'resolve']);
$router->post('/api/transfer', ['ApiController', 'transfer']);
$router->post('/api/deposit', ['ApiController', 'deposit']);
$router->get('/api/deposit/{ref}', ['ApiController', 'depositStatus']);
$router->post('/api/withdraw', ['ApiController', 'withdraw']);
$router->get('/api/destinations', ['ApiController', 'destinations']);
$router->post('/api/ppob', ['ApiController', 'ppob']);
$router->get('/api/transactions', ['ApiController', 'transactions']);
$router->get('/api/notifications', ['ApiController', 'notifications']);
$router->post('/api/pin', ['ApiController', 'setPin']);

// Webhook
$router->post('/webhooks/buatqris', ['WebhookController', 'buatqris']);
$router->post('/webhooks/digiflazz', ['WebhookController', 'digiflazz']);

$match = $router->match($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
if (!$match) {
    // root "/" untuk user login → home
    if ($path === '/') {
        $u = Auth::user();
        if ($u) { redirect($u['role'] === 'user' ? 'home' : 'admin'); }
    }
    http_response_code(404);
    if ($isApi) json_response(['ok' => false, 'message' => 'Tidak ditemukan.'], 404);
    View::render('errors/404', ['title' => 'Tidak ditemukan'], 'public');
    exit;
}

[$ctrl, $method] = $match['handler'];
$class = 'App\\Http\\Controllers\\' . $ctrl;
$instance = new $class();
call_user_func_array([$instance, $method], array_values($match['params']));
