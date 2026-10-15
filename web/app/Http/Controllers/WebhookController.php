<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\DB;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Providers\BuatQrisClient;
use App\Providers\MidtransClient;
use App\Services\DepositService;
use App\Services\MidtransService;
use App\Services\WithdrawService;

/** Webhook publik: verifikasi signature, idempoten, balas cepat 2xx. */
final class WebhookController {
    public function buatqris(): void {
        $raw = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_X_BUATQRIS_SIGNATURE'] ?? $_SERVER['HTTP_X_BUATQRIS_SIGNATURE'] ?? '';
        // Header normalisasi (Apache menamai ulang dengan underscore)
        if ($sig === '') $sig = $_SERVER['HTTP_X_BUATQRIS_SIGNATURE'] ?? '';
        foreach ($_SERVER as $k => $v) {
            if (stripos($k, 'BUATQRIS') !== false && stripos($k, 'SIGNATURE') !== false) { $sig = (string)$v; break; }
        }
        $secret = (string)config('buatqris.signing_secret');
        if ($secret === '') {
            // Belum dikonfigurasi → tolak dengan jelas di log, balas 200 agar
            // provider tidak retry sia-sia, tapi JANGAN proses.
            Logger::log('webhook.no-secret', []);
            http_response_code(200);
            echo 'webhook belum dikonfigurasi';
            return;
        }
        if (!BuatQrisClient::verifySignature((string)$raw, $sig, $secret)) {
            Logger::log('webhook.bad-signature', []);
            http_response_code(401);
            echo 'invalid signature';
            return;
        }
        $payload = json_decode((string)$raw, true);
        if (!is_array($payload)) { http_response_code(400); return; }
        http_response_code(200); // balas cepat
        $event = (string)($payload['event'] ?? '');
        if (str_starts_with($event, 'payment.')) DepositService::applyWebhook($payload);
        elseif (str_starts_with($event, 'withdrawal.')) WithdrawService::applyWebhook($payload);
        try {
            DB::exec("INSERT INTO webhook_events (provider,event,delivery_id,payload,status) VALUES ('buatqris',?,?,?,'received') ON DUPLICATE KEY UPDATE payload=VALUES(payload)", [$event, (string)($payload['transaction_id'] ?? $payload['withdrawal_id'] ?? ''), (string)$raw]);
        } catch (\Throwable $e) {}
        echo 'ok';
    }

    public function digiflazz(): void {
        // DigiFlazz tidak push webhook generik; endpoint ini untuk status
        // callback bila diaktifkan di sisi provider di masa depan.
        Logger::log('webhook.digiflazz', ['note' => 'gunakan /cron/reconcile.php untuk status check']);
        http_response_code(200);
        echo 'ok';
    }

    /**
     * Webhook Midtrans HTTPS Notification (server-to-server).
     * Verifikasi: signature_key SHA512(order_id+status_code+gross_amount+ServerKey).
     * Wajib balas 200 cepat; idempoten; tidak percaya IP sebagai auth.
     */
    public function midtrans(): void {
        // Rate limit longgar anti-spam (DB-based, fail-open).
        $rl = RateLimiter::hit('wh:midtrans:' . client_ip(), 120, 60);
        if (!$rl['allowed']) { http_response_code(429); echo 'rate limited'; return; }

        $raw = (string)file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) { http_response_code(400); echo 'bad payload'; return; }

        $orderId = (string)($payload['order_id'] ?? '');
        $statusCode = (string)($payload['status_code'] ?? '');
        $gross = (string)($payload['gross_amount'] ?? '');
        $sig = (string)($payload['signature_key'] ?? '');
        if ($orderId === '' || $statusCode === '' || $gross === '' || $sig === '') {
            Logger::log('midtrans.bad-format', ['order' => substr($orderId, 0, 40)]);
            http_response_code(400);
            echo 'bad format';
            return;
        }

        $cfg = MidtransClient::resolveConfig();
        if ($cfg['server_key'] === '') {
            Logger::log('webhook.no-secret', ['provider' => 'midtrans']);
            http_response_code(200); // jangan picu retry sia-sia
            echo 'webhook belum dikonfigurasi';
            return;
        }
        if (!MidtransClient::verifySignatureWith($cfg['server_key'], $orderId, $statusCode, $gross, $sig)) {
            Logger::log('webhook.bad-signature', ['provider' => 'midtrans', 'order' => substr($orderId, 0, 40)]);
            try {
                DB::exec("INSERT INTO security_events (user_id, kind, ip, detail) VALUES (NULL,?,?,?)", ['midtrans_bad_signature', client_ip(), substr($orderId, 0, 60)]);
            } catch (\Throwable $e) {}
            http_response_code(401);
            echo 'invalid signature';
            return;
        }

        http_response_code(200); // balas cepat, lalu proses
        MidtransService::applyNotification($payload);
        echo 'ok';
    }
}
