<?php
declare(strict_types=1);
/**
 * Webhook Midtrans — entry langsung untuk cPanel (alternatif routing index.php).
 * URL produksiv: https://domain/webhooks/midtrans.php  ATAU  https://domain/webhooks/midtrans
 * Daftarkan salah satunya di dashboard Midtrans → Settings → Notification URL.
 */
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
(new \App\Http\Controllers\WebhookController())->midtrans();
