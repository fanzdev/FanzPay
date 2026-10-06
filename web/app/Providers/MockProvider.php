<?php
declare(strict_types=1);

namespace App\Providers;

/**
 * Mock deterministik untuk development/sandbox.
 * Labeli dengan is_mock=true agar UI jelas menulis "MODE SANDBOX".
 * JANGAN pernah menampilkan transaksi mock seolah sukses nyata.
 */
final class MockProvider {
    public static function qris(int $amount, string $desc): array {
        $id = 'MOCK' . date('ymdHis') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return ['ok' => true, 'data' => [
            'transaction_id' => $id,
            'amount' => $amount, 'total_amount' => $amount, 'amount_uniq' => 0,
            'admin_fee' => 0, 'admin_fee_pct' => 0, 'credit_amount' => $amount,
            'fee_by' => 'user', 'qris_method' => 'qris_two',
            'description' => $desc, 'umkm_name' => 'FanzPay Sandbox',
            'qris_image' => null, 'qr_url' => null, 'payment_url' => null,
            'expired_at' => date('c', time() + 3600), 'status' => 'pending',
            'is_mock' => true,
        ]];
    }

    public static function withdraw(int $amount): array {
        return ['ok' => true, 'data' => [
            'withdrawal_id' => 'MOCK-WD-' . strtoupper(bin2hex(random_bytes(4))),
            'amount' => $amount, 'fee' => 0, 'net_amount' => $amount,
            'status' => 'pending', 'is_mock' => true,
        ], 'message' => 'Permintaan penarikan (sandbox) dibuat.'];
    }

    public static function balance(): array {
        return ['ok' => true, 'data' => ['deposit' => 10000000, 'is_mock' => true]];
    }

    public static function priceList(): array {
        return ['ok' => true, 'data' => ['is_mock' => true, 'note' => 'Sinkronisasi nyata butuh kredensial DigiFlazz production.']];
    }

    public static function ppob(string $sku, string $customerNo, string $refId): array {
        return ['ok' => true, 'data' => [
            'ref_id' => $refId, 'customer_no' => $customerNo, 'buyer_sku_code' => $sku,
            'status' => 'Pending', 'message' => 'Pesanan (sandbox) sedang diproses.',
            'price' => 0, 'is_mock' => true,
        ]];
    }

    public static function inquiry(string $sku, string $customerNo, string $refId): array {
        return ['ok' => true, 'data' => ['ref_id' => $refId, 'status' => 'Pending', 'is_mock' => true]];
    }
}
