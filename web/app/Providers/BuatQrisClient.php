<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\Logger;

/**
 * Client BuatQris Open API — SELALU dipanggil dari server.
 * Base URL tunggal https://api.buatqris.site, dibedakan via `action`.
 * Dok: prompt/konfigurasi-open-api (*.txt).
 */
class BuatQrisClient {
    private string $accountId;
    private string $secret;
    private string $base;
    private bool $sandbox;

    public function __construct(?string $accountId = null, ?string $secret = null, ?string $base = null, ?bool $sandbox = null) {
        $this->accountId = $accountId ?? (string)config('buatqris.account_id');
        $this->secret = $secret ?? (string)config('buatqris.secret_token');
        $this->base = rtrim($base ?? (string)config('buatqris.base_url'), '/');
        $this->sandbox = $sandbox ?? (bool)config('buatqris.sandbox');
    }

    public function configured(): bool {
        return strlen($this->accountId) > 0 && strlen($this->secret) >= 20;
    }

    public function isSandbox(): bool { return $this->sandbox; }

    /** @return array{ok:bool, data?:array, message?:string, rate_limited?:bool, retry_after?:int} */
    public function createQris(int $amount, string $description, array $opt = []): array {
        if (!$this->configured() || $this->sandbox) {
            return MockProvider::qris($amount, $description);
        }
        $payload = array_merge([
            'action' => 'api_create_qris',
            'account_id' => $this->accountId,
            'secret_token' => $this->secret,
            'amount' => $amount,
            'description' => substr($description, 0, 100),
            'fee_by' => 'user',
            'app_name' => 'FanzPay',
            'app_version' => '1.0.0',
        ], $opt);
        $res = $this->post($payload);
        if (!$res['ok']) return $res;
        $body = $res['body'];
        if (empty($body['success'])) return ['ok' => false, 'message' => (string)($body['message'] ?? 'Gagal membuat QRIS.')];
        return ['ok' => true, 'data' => $body['data']];
    }

    /** @return array{ok:bool, data?:array, message?:string, rate_limited?:bool, retry_after?:int} */
    public function checkStatus(string $transactionId): array {
        if (!$this->configured() || $this->sandbox) return ['ok' => true, 'data' => ['transaction_id' => $transactionId, 'status' => 'pending']];
        $res = $this->post(['action' => 'api_check_status', 'account_id' => $this->accountId, 'secret_token' => $this->secret, 'transaction_id' => $transactionId]);
        if (!$res['ok']) return $res;
        $body = $res['body'];
        if (isset($body['error']) && $body['error'] === 'rate_limited') {
            return ['ok' => false, 'rate_limited' => true, 'retry_after' => (int)($body['retry_after'] ?? 20), 'message' => (string)($body['message'] ?? 'Terlalu sering.')];
        }
        if (empty($body['success'])) return ['ok' => false, 'message' => (string)($body['message'] ?? 'Gagal cek status.')];
        return ['ok' => true, 'data' => $body['data']];
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function withdraw(int $amount, string $wdAccountId, string $wdPin): array {
        if (!$this->configured() || $this->sandbox) return MockProvider::withdraw($amount);
        $res = $this->post(['action' => 'api_withdraw', 'account_id' => $this->accountId, 'secret_token' => $this->secret, 'amount' => $amount, 'wd_account_id' => $wdAccountId, 'wd_pin' => $wdPin]);
        if (!$res['ok']) return $res;
        $body = $res['body'];
        if (empty($body['success'])) return ['ok' => false, 'message' => (string)($body['message'] ?? 'Penarikan ditolak provider.')];
        return ['ok' => true, 'data' => $body['data'], 'message' => (string)($body['message'] ?? 'ok')];
    }

    private function post(array $payload): array {
        $ch = curl_init($this->base);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'X-App-Name: FanzPay', 'X-App-Version: 1.0.0'],
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            Logger::log('buatqris.http-error', ['code' => $code]);
            return ['ok' => false, 'message' => 'Transaksi sedang diproses.'];
        }
        $body = json_decode((string)$raw, true);
        if (!is_array($body)) return ['ok' => false, 'message' => 'Transaksi sedang diproses.'];
        if ($code === 429) return ['ok' => false, 'rate_limited' => true, 'retry_after' => (int)($body['retry_after'] ?? 20), 'message' => (string)($body['message'] ?? 'Terlalu sering.')];
        return ['ok' => true, 'body' => $body];
    }

    /** Verifikasi webhook HMAC-SHA256 memakai body MENTAH. */
    public static function verifySignature(string $rawBody, string $signature, ?string $secret = null): bool {
        $secret = $secret ?? (string)config('buatqris.signing_secret');
        if ($secret === '' || $signature === '') return false;
        $calc = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($calc, $signature);
    }
}
