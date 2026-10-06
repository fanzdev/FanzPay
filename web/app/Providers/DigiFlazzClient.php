<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\Logger;

/**
 * Client DigiFlazz v1 (server-side). Signature: md5(username + apiKey + refId).
 * Seluruh logic provider terisolasi di sini — mudah diperbarui mengikuti
 * dokumentasi resmi DigiFlazz tanpa menyentuh service lain.
 */
class DigiFlazzClient {
    private string $user;
    private string $key;
    private string $base;
    private bool $sandbox;

    public function __construct(?string $user = null, ?string $key = null, ?string $base = null, ?bool $sandbox = null) {
        $this->user = $user ?? (string)config('digiflazz.username');
        $this->key = $key ?? (string)config('digiflazz.api_key');
        $this->base = rtrim($base ?? (string)config('digiflazz.base_url'), '/');
        $this->sandbox = $sandbox ?? (bool)config('digiflazz.sandbox');
    }

    public function configured(): bool { return $this->user !== '' && $this->key !== ''; }
    public function isSandbox(): bool { return $this->sandbox; }

    public static function sign(string $user, string $key, string $refId): string {
        return md5($user . $key . $refId);
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function balance(): array {
        if (!$this->configured() || $this->sandbox) return MockProvider::balance();
        $r = $this->call('/cek-saldo', ['username' => $this->user, 'sign' => md5($this->user . $this->key . 'depo')]);
        if (!$r['ok']) return $r;
        // Sukses cek-saldo: {"data":{"deposit":N}} (tanpa rc). Tolak hanya bila rc eksplisit gagal.
        if (isset($r['data']['rc']) && $r['data']['rc'] !== '00') return ['ok' => false, 'message' => 'DigiFlazz: ' . ($r['data']['message'] ?? 'gagal cek saldo.')];
        return $r;
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function priceList(string $cmd = 'prepaid', string $code = ''): array {
        if (!$this->configured() || $this->sandbox) return MockProvider::priceList();
        $payload = ['username' => $this->user, 'sign' => md5($this->user . $this->key . 'pricelist'), 'cmd' => $cmd];
        if ($code !== '') $payload['code'] = $code;
        $r = $this->call('/price-list', $payload);
        if (!$r['ok']) return $r;
        // Sukses price-list: rc "00" + sub-array data. rc lain (mis. 83 = limit) = gagal jelas.
        if (isset($r['data']['rc']) && $r['data']['rc'] !== '00') {
            return ['ok' => false, 'message' => 'DigiFlazz: ' . ($r['data']['message'] ?? 'price-list tidak tersedia.')];
        }
        if (!isset($r['data']['data'])) {
            return ['ok' => false, 'message' => 'DigiFlazz: price-list tidak tersedia.'];
        }
        return $r;
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function transaction(string $sku, string $customerNo, string $refId, string $cmd = 'prepaid', bool $testing = false): array {
        if (!$this->configured() || $this->sandbox) return MockProvider::ppob($sku, $customerNo, $refId);
        return $this->call('/transaksi', [
            'username' => $this->user,
            'buyer_sku_code' => $sku,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => self::sign($this->user, $this->key, $refId),
            'cmd' => $cmd,
            'testing' => $testing,
        ]);
    }

    /** @return array{ok:bool, data?:array, message?:string} */
    public function inquiry(string $sku, string $customerNo, string $refId, string $cmd = 'pasca'): array {
        if (!$this->configured() || $this->sandbox) return MockProvider::inquiry($sku, $customerNo, $refId);
        return $this->call('/transaksi', [
            'username' => $this->user, 'buyer_sku_code' => $sku, 'customer_no' => $customerNo,
            'ref_id' => $refId, 'sign' => self::sign($this->user, $this->key, $refId), 'cmd' => $cmd,
        ]);
    }

    private function call(string $path, array $payload): array {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            Logger::log('digiflazz.http-error', ['code' => $code, 'path' => $path]);
            return ['ok' => false, 'message' => 'Transaksi sedang diproses.'];
        }
        $body = json_decode((string)$raw, true);
        if (!is_array($body)) return ['ok' => false, 'message' => 'Transaksi sedang diproses.'];
        $data = $body['data'] ?? null;
        if (!is_array($data)) return ['ok' => false, 'message' => (string)($body['message'] ?? 'Provider tidak merespons.')];
        return ['ok' => true, 'data' => $data];
    }
}
