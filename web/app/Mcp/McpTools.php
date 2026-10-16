<?php
declare(strict_types=1);

namespace App\Mcp;

/**
 * Definisi 18 MCP tools: nama, scope, skema input ketat.
 * PIN/OTP tidak pernah menjadi field input mana pun.
 */
final class McpTools {
    /** @return array<string,array{description:string, scope:string}> */
    public static function definitions(): array {
        return [
            'get_my_profile' => ['description' => 'Profil dasar akun terautentikasi (nama, email, phone termasking, public_id, role, status).', 'scope' => 'read'],
            'get_my_balance' => ['description' => 'Saldo tersedia + saldo tertahan milik akun terautentikasi. Read-only.', 'scope' => 'read'],
            'get_my_transaction_history' => ['description' => 'Riwayat transaksi milik sendiri. Mendukung pagination + filter kind/status/date.', 'scope' => 'read'],
            'get_my_transaction_status' => ['description' => 'Status satu transaksi milik sendiri berdasarkan reference_id.', 'scope' => 'read'],
            'get_my_deposit_status' => ['description' => 'Status deposit milik sendiri (data backend terverifikasi, bukan klaim redirect).', 'scope' => 'read'],
            'search_ppob_products' => ['description' => 'Cari produk PPOB aktif dari katalog FanzPay (kategori/keyword). Hanya kembalikan harga jual.', 'scope' => 'read'],
            'compare_ppob_products' => ['description' => 'Bandingkan 2-5 produk berdasarkan kode katalog. Hanya atribut yang tersedia.', 'scope' => 'read'],
            'recommend_ppob_products' => ['description' => 'Rekomendasi produk dari kebutuhan + anggaran + saldo (bila diizinkan). Bukan otorisasi beli.', 'scope' => 'read'],
            'get_ppob_product_details' => ['description' => 'Detail satu produk aktif berdasarkan kode katalog.', 'scope' => 'read'],
            'prepare_ppob_purchase' => ['description' => 'Siapkan pembelian: validasi + ringkasan + preparation_id 10 menit. TANPA debit.', 'scope' => 'read'],
            'execute_ppob_purchase' => ['description' => 'Eksekusi pembelian yang sudah diprepare dan lolos kebijakan pembelian otomatis. Idempoten.', 'scope' => 'purchase'],
            'get_purchase_policy' => ['description' => 'Baca status dan batas pembelian otomatis yang berlaku untuk akun ini.', 'scope' => 'read'],
            'get_my_spending_summary' => ['description' => 'Ringkasan pengeluaran mingguan/bulanan dari riwayat (berhasil/pending/gagal/refund terpisah).', 'scope' => 'read'],
            'explain_my_transaction' => ['description' => 'Jelaskan satu transaksi milik sendiri dari catatan sistem (tanpa mengarang).', 'scope' => 'read'],
            'search_fanzpay_help' => ['description' => 'Cari jawaban dari basis pengetahuan resmi FanzPay.', 'scope' => 'read'],
            'create_customer_support_ticket' => ['description' => 'Buat tiket bantuan (tanpa janji refund/saldo). Kembalikan nomor tiket.', 'scope' => 'read'],
            'get_fanzpay_promotions' => ['description' => 'Promo aktif yang benar-benar tersedia beserta periode dan syarat.', 'scope' => 'read'],
            'get_ppob_reminders' => ['description' => 'Pengingat PPOB milik akun (tidak mengirim notifikasi).', 'scope' => 'read'],
        ];
    }

    /** @return array<string,array> JSON Schema per tool (digunakan tools/list). */
    public static function inputSchema(string $tool): array {
        $str = fn(int $max) => ['type' => 'string', 'maxLength' => $max];
        $int = fn(int $min, int $max) => ['type' => 'integer', 'minimum' => $min, 'maximum' => $max];
        return match ($tool) {
            'get_my_profile', 'get_my_balance', 'get_purchase_policy', 'get_ppob_reminders' =>
                ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            'get_my_transaction_history' => ['type' => 'object', 'properties' => [
                'limit' => $int(1, 50), 'offset' => $int(0, 10000),
                'kind' => ['type' => 'string', 'enum' => ['deposit', 'withdraw', 'transfer', 'ppob', 'refund', '']],
                'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'success', 'failed', 'expired', 'refunded', 'reversed', '']],
                'from' => $str(10), 'to' => $str(10),
            ], 'additionalProperties' => false],
            'get_my_transaction_status', 'explain_my_transaction' => ['type' => 'object', 'properties' => [
                'reference_id' => $str(64),
            ], 'required' => ['reference_id'], 'additionalProperties' => false],
            'get_my_deposit_status' => ['type' => 'object', 'properties' => [
                'reference_id' => $str(64),
            ], 'required' => ['reference_id'], 'additionalProperties' => false],
            'search_ppob_products' => ['type' => 'object', 'properties' => [
                'query' => $str(80), 'category' => $str(40), 'limit' => $int(1, 50),
            ], 'additionalProperties' => false],
            'compare_ppob_products' => ['type' => 'object', 'properties' => [
                'codes' => ['type' => 'array', 'minItems' => 2, 'maxItems' => 5, 'items' => $str(60)],
            ], 'required' => ['codes'], 'additionalProperties' => false],
            'recommend_ppob_products' => ['type' => 'object', 'properties' => [
                'need' => $str(200), 'category' => $str(40), 'budget' => $int(0, 100000000), 'include_balance' => ['type' => 'boolean'],
            ], 'additionalProperties' => false],
            'get_ppob_product_details' => ['type' => 'object', 'properties' => [
                'code' => $str(60),
            ], 'required' => ['code'], 'additionalProperties' => false],
            'prepare_ppob_purchase' => ['type' => 'object', 'properties' => [
                'product_code' => $str(60), 'customer_no' => $str(40),
            ], 'required' => ['product_code', 'customer_no'], 'additionalProperties' => false],
            'execute_ppob_purchase' => ['type' => 'object', 'properties' => [
                'preparation_id' => $str(48), 'idempotency_key' => $str(80),
            ], 'required' => ['preparation_id'], 'additionalProperties' => false],
            'get_my_spending_summary' => ['type' => 'object', 'properties' => [
                'period' => ['type' => 'string', 'enum' => ['week', 'month']],
            ], 'additionalProperties' => false],
            'search_fanzpay_help' => ['type' => 'object', 'properties' => [
                'question' => $str(300),
            ], 'required' => ['question'], 'additionalProperties' => false],
            'create_customer_support_ticket' => ['type' => 'object', 'properties' => [
                'subject' => $str(150), 'message' => $str(1000), 'reference_id' => $str(64),
            ], 'required' => ['subject', 'message'], 'additionalProperties' => false],
            'get_fanzpay_promotions' => ['type' => 'object', 'properties' => [
                'category' => $str(40),
            ], 'additionalProperties' => false],
            default => ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
        };
    }

    /** Validasi server-side: tolak PIN/OTP, batasi ukuran, whitelist enum. */
    public static function validate(string $tool, mixed $args): array {
        if (!is_array($args)) $args = [];
        if (count($args) > 20) throw new McpException(-32602, 'Terlalu banyak parameter.');
        $flat = json_encode($args, JSON_UNESCAPED_UNICODE) ?? '';
        if (strlen($flat) > 8000) throw new McpException(-32602, 'Argumen terlalu besar.');
        if (preg_match('/\b(pin|otp|password|cvv)\b/i', $flat)) {
            \App\Core\Logger::audit(null, null, 'mcp.pin_blocked', 'mcp', $tool, 'fail', []);
            throw new McpException(-32602, 'PIN/OTP/kata sandi tidak boleh dikirim via AI. Gunakan aplikasi/web resmi FanzPay.');
        }
        foreach ($args as $k => $v) {
            if (is_string($v) && strlen($v) > 2000) throw new McpException(-32602, "Field {$k} terlalu panjang.");
        }
        return $args;
    }
}
