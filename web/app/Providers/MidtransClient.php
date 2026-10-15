<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\Logger;
use App\Services\Settings;

/**
 * Midtrans Core API client (HTTP langsung, tanpa SDK — cPanel-friendly).
 * Dok: https://docs.midtrans.com/reference, https://docs.midtrans.com/docs/https-notification-webhooks
 *
 * - Auth: Basic base64(ServerKey + ":") pada setiap request.
 * - ServerKey HANYA di backend. Tidak pernah ke browser/log.
 * - Timeout eksplisit 20 dtk; timeout TIDAK berarti gagal (wajib cek status).
 */
class MidtransClient {
    private string $serverKey;
    private string $clientKey;
    private string $mode; // sandbox|production

    public function __construct(?string $serverKey = null, ?string $clientKey = null, ?string $mode = null) {
        $resolved = self::resolveConfig();
        $this->serverKey = $serverKey ?? $resolved['server_key'];
        $this->clientKey = $clientKey ?? $resolved['client_key'];
        $this->mode = strtolower($mode ?? $resolved['mode']) === 'production' ? 'production' : 'sandbox';
    }

    /** Resolusi kredensial: .env prioritas, fallback system_settings (panel admin). */
    public static function resolveConfig(): array {
        $envServer = (string)config('midtrans.server_key');
        $envClient = (string)config('midtrans.client_key');
        $envMode = (string)config('midtrans.mode');
        try {
            $db = Settings::all();
        } catch (\Throwable $e) { $db = []; }
        $server = $envServer !== '' ? $envServer : (string)($db['midtrans_server_key'] ?? '');
        $client = $envClient !== '' ? $envClient : (string)($db['midtrans_client_key'] ?? '');
        $mode = $envMode !== '' ? $envMode : (string)($db['midtrans_mode'] ?? 'sandbox');
        // Kompat: kunci lama bernama midtrans_server / midtrans_client
        if ($server === '' && isset($db['midtrans_server'])) $server = (string)$db['midtrans_server'];
        if ($client === '' && isset($db['midtrans_client'])) $client = (string)$db['midtrans_client'];
        return ['server_key' => $server, 'client_key' => $client, 'mode' => $mode === 'production' ? 'production' : 'sandbox'];
    }

    public function configured(): bool {
        return strlen($this->serverKey) >= 20;
    }

    public function isProduction(): bool { return $this->mode === 'production'; }
    public function mode(): string { return $this->mode; }

    public function clientKey(): string { return $this->clientKey; }

    private function apiBase(): string {
        return $this->isProduction() ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    /** @return array{ok:bool, data?:array, message?:string, http?:int} */
    public function charge(array $payload): array {
        if (!$this->configured()) return ['ok' => false, 'message' => 'Midtrans belum dikonfigurasi.'];
        $res = $this->request('POST', $this->apiBase() . '/v2/charge', $payload);
        if (!$res['ok']) return $res;
        $body = $res['body'];
        if (!is_array($body) || !isset($body['status_code']) || !in_array((string)$body['status_code'], ['200', '201'], true)) {
            $msg = is_array($body) ? (string)($body['status_message'] ?? 'Gagal membuat transaksi.') : 'Respons Midtrans tidak valid.';
            Logger::log('midtrans.charge-rejected', ['code' => $body['status_code'] ?? '?', 'order' => $payload['transaction_details']['order_id'] ?? '?']);
            return ['ok' => false, 'message' => $this->friendlyError($msg), 'http' => $res['http']];
        }
        return ['ok' => true, 'data' => $body, 'http' => $res['http']];
    }

    /** @return array{ok:bool, data?:array, message?:string, http?:int} */
    public function status(string $orderId): array {
        if (!$this->configured()) return ['ok' => false, 'message' => 'Midtrans belum dikonfigurasi.'];
        $res = $this->request('GET', $this->apiBase() . '/v2/' . rawurlencode($orderId) . '/status');
        if (!$res['ok']) return $res;
        if (!is_array($res['body'])) return ['ok' => false, 'message' => 'Respons status tidak valid.'];
        return ['ok' => true, 'data' => $res['body'], 'http' => $res['http']];
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function cancel(string $orderId): array {
        $res = $this->request('POST', $this->apiBase() . '/v2/' . rawurlencode($orderId) . '/cancel');
        if (!$res['ok']) return $res;
        return ['ok' => true, 'data' => is_array($res['body']) ? $res['body'] : []];
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function expire(string $orderId): array {
        $res = $this->request('POST', $this->apiBase() . '/v2/' . rawurlencode($orderId) . '/expire');
        if (!$res['ok']) return $res;
        return ['ok' => true, 'data' => is_array($res['body']) ? $res['body'] : []];
    }

    /**
     * Uji konektivitas tanpa membuat transaksi: status order fiktif.
     * 401 = key salah; 404 = key valid (order tidak ada); 200 = order ada.
     * @return array{ok:bool, message:string, valid:bool}
     */
    public function testConnection(): array {
        if (!$this->configured()) return ['ok' => false, 'valid' => false, 'message' => 'Server Key belum diisi.'];
        $res = $this->request('GET', $this->apiBase() . '/v2/__fanzpay_ping_' . date('His') . '/status');
        $http = (int)($res['http'] ?? 0);
        if ($http === 401) return ['ok' => false, 'valid' => false, 'message' => 'Server Key ditolak Midtrans (401). Periksa mode Sandbox/Production.'];
        if ($http === 404) return ['ok' => true, 'valid' => true, 'message' => 'Terkoneksi (' . $this->mode . '). Key valid — order tes wajar 404.'];
        if ($http === 200) return ['ok' => true, 'valid' => true, 'message' => 'Terkoneksi (' . $this->mode . ').'];
        if (!$res['ok']) return ['ok' => false, 'valid' => false, 'message' => (string)($res['message'] ?? 'Gagal koneksi.')];
        return ['ok' => true, 'valid' => true, 'message' => 'Terkoneksi (' . $this->mode . ').'];
    }

    /**
     * Verifikasi signature notifikasi resmi:
     * signature_key = SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool {
        if ($this->serverKey === '' || $signatureKey === '') return false;
        $calc = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals(strtolower($calc), strtolower($signatureKey));
    }

    public static function verifySignatureWith(string $serverKey, string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool {
        if ($serverKey === '' || $signatureKey === '') return false;
        return hash_equals(strtolower(hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey)), strtolower($signatureKey));
    }

    /** @return array{ok:bool, body?:mixed, message?:string, http?:int} */
    private function request(string $method, string $url, ?array $payload = null): array {
        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            'X-App-Name: FanzPay',
        ];
        $opt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($payload !== null) $opt[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        curl_setopt_array($ch, $opt);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            Logger::log('midtrans.http-error', ['http' => $http, 'note' => 'timeout/koneksi — status UNKNOWN, wajib cek status']);
            return ['ok' => false, 'message' => 'Koneksi ke Midtrans gagal/timeout. Jangan buat transaksi pengganti sebelum cek status.', 'http' => $http];
        }
        $body = json_decode((string)$raw, true);
        if ($http === 401) {
            Logger::log('midtrans.unauthorized', ['http' => 401]);
            return ['ok' => false, 'message' => 'Otorisasi Midtrans ditolak (401). Periksa Server Key & mode.', 'http' => 401];
        }
        if ($http === 404 && str_contains($url, '/status')) {
            return ['ok' => false, 'message' => 'Transaksi tidak ditemukan di Midtrans.', 'http' => 404, 'body' => is_array($body) ? $body : []];
        }
        if (!is_array($body)) {
            return ['ok' => false, 'message' => 'Respons Midtrans tidak valid.', 'http' => $http];
        }
        if ($http >= 500) {
            Logger::log('midtrans.5xx', ['http' => $http]);
            return ['ok' => false, 'message' => 'Midtrans gangguan sementara. Coba lagi / cek status.', 'http' => $http, 'body' => $body];
        }
        if ($http === 429) return ['ok' => false, 'message' => 'Terlalu banyak permintaan ke Midtrans. Tunggu sebentar.', 'http' => 429, 'body' => $body];
        return ['ok' => true, 'body' => $body, 'http' => $http];
    }

    private function friendlyError(string $msg): string {
        $m = strtolower($msg);
        if (str_contains($m, 'gross_amount')) return 'Nominal tidak valid untuk Midtrans.';
        if (str_contains($m, 'order_id') && str_contains($m, 'used')) return 'Referensi sudah dipakai. Ulangi pembuatan deposit.';
        if (str_contains($m, 'payment_type')) return 'Metode pembayaran tidak didukung / belum aktif di akun Midtrans.';
        return $msg !== '' ? $msg : 'Gagal membuat transaksi.';
    }
}
