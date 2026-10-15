<?php
declare(strict_types=1);
/**
 * Cron Midtrans: sinkronkan deposit pending via Status API + tandai kedaluwarsa lokal.
 * cPanel Cron (tiap 5 menit):
 *   php /home/USER/public_html/web/cron/reconcile_midtrans.php
 * atau dengan key HTTP (opsional, isi CRON_KEY di env):
 *   curl -s "https://domain/cron/reconcile_midtrans.php?key=RAHASIA"
 */
$__cronKey = (string)(getenv('CRON_KEY') ?: '');
if (PHP_SAPI !== 'cli' && ($__cronKey === '' || ($_GET['key'] ?? '') !== $__cronKey)) { http_response_code(403); exit('forbidden'); }
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
use App\Core\Logger;
use App\Services\MidtransService;

try {
    $expired = MidtransService::expireLocal(100);
    $sync = MidtransService::syncPending(20);
    Logger::log('cron.midtrans', ['expired' => $expired] + $sync);
    echo 'expired=' . $expired . ' checked=' . $sync['checked'] . ' credited=' . $sync['credited'] . ' expired_api=' . $sync['expired'] . ' failed=' . $sync['failed'] . "\n";
} catch (Throwable $e) {
    Logger::log('cron.error', ['job' => 'midtrans']);
    echo "error\n";
}
