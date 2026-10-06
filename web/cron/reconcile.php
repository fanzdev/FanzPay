<?php
declare(strict_types=1);
/**
 * Cron: tandai deposit pending yang kedaluwarsa + laporkan pending lama.
 * cPanel Cron: php /home/user/public_html/cron/reconcile.php
 * atau: curl -s https://domain/webhooks/digiflazz (hanya logging)
 */
if (PHP_SAPI !== 'cli' && ($_GET['key'] ?? '') !== (getenv('CRON_KEY') ?: '')) {
    // Izinkan via HTTP hanya bila CRON_KEY cocok; default tolak.
    if ((getenv('CRON_KEY') ?: '') === '') { http_response_code(403); exit('forbidden'); }
}
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
use App\Core\DB;
use App\Core\Logger;

$expired = 0;
try {
    $rows = DB::all("SELECT id, transaction_id, reference_id FROM deposits WHERE status='pending' AND expired_at IS NOT NULL AND expired_at < NOW() LIMIT 100");
    foreach ($rows as $r) {
        DB::exec("UPDATE deposits SET status='expired' WHERE id=? AND status='pending'", [(int)$r['id']]);
        DB::exec("UPDATE transactions SET status='expired' WHERE id=?", [(int)$r['transaction_id']]);
        $expired++;
    }
    $pending = DB::one("SELECT COUNT(*) c FROM transactions WHERE status IN ('pending','processing') AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    Logger::log('cron.reconcile', ['expired' => $expired, 'old_pending' => (int)($pending['c'] ?? 0)]);
    echo "expired={$expired} old_pending=" . (int)($pending['c'] ?? 0) . "\n";
} catch (Throwable $e) {
    Logger::log('cron.error', ['msg' => substr($e->getMessage(), 0, 200)]);
    echo "error\n";
}
