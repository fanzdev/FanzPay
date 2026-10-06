<?php
declare(strict_types=1);
/**
 * CLI: php web/sync.php
 * Menarik katalog REAL DigiFlazz (price-list prepaid) ke database lokal.
 * Butuh DIGIFLAZZ_USERNAME + DIGIFLAZZ_API_KEY di web/.env.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/config/config.php';
require __DIR__ . '/app/Core/helpers.php';
spl_autoload_register(function (string $c): void {
    if (!str_starts_with($c, 'App\\')) return;
    $f = __DIR__ . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});

echo "FanzPay catalog sync (DigiFlazz prepaid)\n=====================================\n";
$bal = (new App\Providers\DigiFlazzClient())->balance();
if (!$bal['ok']) { echo "GAGAL konek DigiFlazz: " . ($bal['message'] ?? '?') . "\n"; exit(1); }
echo 'Saldo DigiFlazz: Rp ' . number_format((int)($bal['data']['deposit'] ?? 0), 0, ',', '.') . "\n";

$r = App\Services\CatalogSyncService::syncPrepaid();
if (!$r['ok']) { echo 'GAGAL: ' . $r['message'] . "\n"; exit(1); }
echo "OK: {$r['skus']} SKU tersimpan, {$r['active']} aktif dijual.\n";
