<?php
declare(strict_types=1);

namespace App\Mcp;

use App\Core\DB;
use App\Core\Logger;
use App\Services\McpPolicyService;
use App\Services\McpPurchaseService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\Settings;
use App\Services\WalletService;

/**
 * Eksekutor 18 tools MCP. Semua query terikat user_id pemilik token.
 * Tidak pernah mengembalikan: password_hash, pin_hash, otp, token, secret, base_price, margin.
 */
final class McpRunner {
    /** @return array hasil terstruktur untuk result.content */
    public static function call(string $tool, array $args, array $user): array {
        $uid = (int)$user['id'];
        return match ($tool) {
            'get_my_profile' => self::profile($user),
            'get_my_balance' => self::balance($uid),
            'get_my_transaction_history' => self::history($uid, $args),
            'get_my_transaction_status' => self::txStatus($uid, $args),
            'get_my_deposit_status' => self::depositStatus($uid, $args),
            'search_ppob_products' => self::search($args),
            'compare_ppob_products' => self::compare($args),
            'recommend_ppob_products' => self::recommend($uid, $args),
            'get_ppob_product_details' => self::details($args),
            'prepare_ppob_purchase' => self::prepare($uid, $args),
            'execute_ppob_purchase' => McpPurchaseService::execute($uid, (string)($args['preparation_id'] ?? ''), (string)($args['idempotency_key'] ?? '')) + ['_audit' => 'mcp.execute'],
            'get_purchase_policy' => ['policy' => McpPolicyService::forUser($uid)],
            'get_my_spending_summary' => self::spending($uid, $args),
            'explain_my_transaction' => self::explain($uid, $args),
            'search_fanzpay_help' => self::help((string)($args['question'] ?? '')),
            'create_customer_support_ticket' => self::ticket($uid, $args),
            'get_fanzpay_promotions' => self::promos($args),
            'get_ppob_reminders' => self::reminders($uid),
            default => throw new McpException(-32601, 'Tool tidak dikenal: ' . $tool),
        };
    }

    private static function profile(array $u): array {
        return ['profile' => [
            'name' => (string)($u['name'] ?? ''),
            'email' => (string)($u['email'] ?? ''),
            'phone' => self::maskPhone((string)($u['phone'] ?? '')),
            'public_id' => (string)($u['public_id'] ?? ''),
            'role' => (string)($u['role'] ?? 'user'),
            'status' => (string)($u['status'] ?? 'active'),
        ]];
    }

    private static function balance(int $uid): array {
        $w = WalletService::getOrCreate($uid);
        return ['balance' => [
            'available' => (int)($w['balance'] ?? 0),
            'available_rp' => rupiah((int)($w['balance'] ?? 0)),
            'locked' => (int)($w['locked'] ?? 0),
            'currency' => 'IDR',
        ]];
    }

    private static function history(int $uid, array $a): array {
        $limit = min(50, max(1, (int)($a['limit'] ?? 20)));
        $offset = max(0, min(10000, (int)($a['offset'] ?? 0)));
        $kind = (string)($a['kind'] ?? '');
        $status = (string)($a['status'] ?? '');
        $from = (string)($a['from'] ?? '');
        $to = (string)($a['to'] ?? '');
        $where = 'user_id = ?';
        $p = [$uid];
        if (in_array($kind, ['deposit', 'withdraw', 'transfer', 'ppob', 'refund'], true)) { $where .= ' AND kind = ?'; $p[] = $kind; }
        if (in_array($status, ['pending', 'processing', 'success', 'failed', 'expired', 'refunded', 'reversed'], true)) { $where .= ' AND status = ?'; $p[] = $status; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where .= ' AND DATE(created_at) >= ?'; $p[] = $from; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where .= ' AND DATE(created_at) <= ?'; $p[] = $to; }
        $p[] = $limit; $p[] = $offset;
        $rows = DB::all("SELECT reference_id, kind, amount, fee, total, status, note, created_at FROM transactions WHERE {$where} ORDER BY id DESC LIMIT ? OFFSET ?", $p);
        return ['transactions' => $rows, 'limit' => $limit, 'offset' => $offset];
    }

    private static function txStatus(int $uid, array $a): array {
        $ref = mb_substr(trim((string)($a['reference_id'] ?? '')), 0, 64);
        if ($ref === '') throw new McpException(-32602, 'reference_id wajib diisi.');
        $t = DB::one('SELECT reference_id, kind, amount, fee, total, status, note, created_at FROM transactions WHERE reference_id = ? AND user_id = ? LIMIT 1', [$ref, $uid]);
        if (!$t) throw new McpException(-32602, 'Transaksi tidak ditemukan pada akun ini.');
        return ['transaction' => $t, 'hint' => self::statusHint((string)$t['status'])];
    }

    private static function depositStatus(int $uid, array $a): array {
        $ref = mb_substr(trim((string)($a['reference_id'] ?? '')), 0, 64);
        if ($ref === '') throw new McpException(-32602, 'reference_id wajib diisi.');
        try {
            $cols = array_column(DB::all('SHOW COLUMNS FROM deposits'), 'Field');
        } catch (\Throwable $e) { $cols = []; }
        $gw = in_array('gateway', $cols, true) ? ', gateway' : '';
        $d = DB::one("SELECT reference_id, status, amount, fee, total_amount, credit_amount, expired_at, paid_at, is_mock{$gw} FROM deposits WHERE reference_id = ? AND user_id = ? LIMIT 1", [$ref, $uid]);
        if (!$d) throw new McpException(-32602, 'Deposit tidak ditemukan pada akun ini.');
        $d['verified'] = true;
        $d['proof_note'] = 'Status berasal dari data backend terverifikasi. Redirect pembayaran BUKAN bukti bayar.';
        return ['deposit' => $d];
    }

    private static function search(array $a): array {
        $q = mb_substr(trim((string)($a['query'] ?? '')), 0, 80);
        $cat = mb_substr(trim(strtolower((string)($a['category'] ?? ''))), 0, 40);
        $limit = min(50, max(1, (int)($a['limit'] ?? 20)));
        $items = PricingService::catalog($cat !== '' ? $cat : null);
        if ($q !== '') {
            $ql = strtolower($q);
            $items = array_values(array_filter($items, fn($it) =>
                str_contains(strtolower((string)($it['name'] ?? '')), $ql) || str_contains(strtolower((string)($it['code'] ?? '')), $ql)));
        }
        return ['products' => array_slice(array_map([self::class, 'pubProduct'], $items), 0, $limit), 'count' => min(count($items), $limit)];
    }

    private static function compare(array $a): array {
        $codes = $a['codes'] ?? [];
        if (!is_array($codes) || count($codes) < 2 || count($codes) > 5) throw new McpException(-32602, 'codes harus array 2-5 kode produk.');
        $out = [];
        foreach (array_slice($codes, 0, 5) as $c) {
            $d = self::productRow(mb_substr((string)$c, 0, 60));
            if ($d) $out[] = $d;
        }
        if (count($out) < 2) throw new McpException(-32602, 'Minimal 2 produk valid untuk dibandingkan.');
        return ['comparison' => $out, 'disclaimer' => 'Hanya atribut katalog yang tersedia. Tidak ada klaim termurah tanpa data pembanding.'];
    }

    private static function recommend(int $uid, array $a): array {
        $need = mb_substr(trim((string)($a['need'] ?? '')), 0, 200);
        $cat = mb_substr(trim(strtolower((string)($a['category'] ?? ''))), 0, 40);
        $budget = max(0, min(100000000, (int)($a['budget'] ?? 0)));
        $withBal = !empty($a['include_balance']);
        $items = PricingService::catalog($cat !== '' ? $cat : null);
        if ($budget > 0) $items = array_values(array_filter($items, fn($it) => (int)($it['price'] ?? 0) <= $budget));
        $items = array_slice($items, 0, 5);
        $recs = [];
        foreach ($items as $it) {
            $recs[] = self::pubProduct($it) + ['reason' => self::reason($it, $need, $budget)];
        }
        $out = ['recommendations' => $recs];
        if ($withBal) $out['balance'] = self::balance($uid)['balance'];
        $out['note'] = 'Rekomendasi bukan otorisasi pembelian. Beli via prepare_ppob_purchase + execute_ppob_purchase sesuai kebijakan.';
        return $out;
    }

    private static function details(array $a): array {
        $code = mb_substr(trim((string)($a['code'] ?? '')), 0, 60);
        if ($code === '') throw new McpException(-32602, 'code wajib diisi.');
        $d = self::productRow($code);
        if (!$d) throw new McpException(-32602, 'Produk tidak ditemukan / nonaktif.');
        return ['product' => $d];
    }

    private static function prepare(int $uid, array $a): array {
        return McpPurchaseService::prepare($uid, (string)($a['product_code'] ?? ''), (string)($a['customer_no'] ?? ''));
    }

    private static function spending(int $uid, array $a): array {
        $period = ($a['period'] ?? 'month') === 'week' ? 'week' : 'month';
        $days = $period === 'week' ? 7 : 30;
        $rows = DB::all(
            "SELECT status, kind, COUNT(*) c, COALESCE(SUM(CASE WHEN status='success' THEN total ELSE 0 END),0) v FROM transactions WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY status, kind",
            [$uid]
        );
        $byStatus = ['success' => 0, 'pending' => 0, 'failed' => 0, 'refunded' => 0];
        foreach ($rows as $r) {
            $st = (string)$r['status'];
            $bucket = in_array($st, ['pending', 'processing'], true) ? 'pending' : (in_array($st, ['failed', 'expired'], true) ? 'failed' : ($st === 'success' ? 'success' : 'refunded'));
            $byStatus[$bucket] += (int)$r['v'];
        }
        return ['period' => $period, 'days' => $days, 'by_status_rp' => $byStatus, 'breakdown' => $rows,
            'note' => 'Transaksi pending BUKAN pembelian final sampai berstatus success.'];
    }

    private static function explain(int $uid, array $a): array {
        $t = self::txStatus($uid, $a)['transaction'];
        $ref = (string)$t['reference_id'];
        $prov = DB::one('SELECT provider_sku, status, provider_ref FROM provider_transactions WHERE transaction_id = (SELECT id FROM transactions WHERE reference_id = ?)', [$ref]);
        return ['explanation' => [
            'reference_id' => $ref, 'kind' => $t['kind'], 'status' => $t['status'],
            'amount_rp' => rupiah((int)$t['total']), 'created_at' => $t['created_at'], 'note' => $t['note'],
            'provider' => $prov ?: null,
            'meaning' => self::statusHint((string)$t['status']),
        ]];
    }

    private static function help(string $q): array {
        $kb = [
            ['k' => ['deposit', 'saldo', 'qris', 'isi'], 'a' => 'Isi saldo: masuk dashboard → Deposit → pilih nominal & metode (QRIS/VA/e-wallet) → bayar sebelum kedaluwarsa → status dipantau di Riwayat/Deposit. Saldo masuk otomatis setelah pembayaran terverifikasi.'],
            ['k' => ['pin'], 'a' => 'PIN 6 digit dipakai untuk transfer, PPOB, dan withdraw via web/app resmi. Jangan pernah kirim PIN ke AI. PIN salah 5x mengunci 15 menit.'],
            ['k' => ['gagal', 'refund', 'pending'], 'a' => 'Transaksi gagal otomatis di-refund ke saldo (status refunded). Status pending/processing berarti menunggu provider — jangan ulangi pembayaran yang sama; cek get_my_transaction_status.'],
            ['k' => ['transfer'], 'a' => 'Transfer antar pengguna gratis (Rp0) via nomor HP / QR di aplikasi. MCP tidak menyediakan transfer.'],
            ['k' => ['mcp', 'ai', 'chatgpt', 'claude', 'token', 'koneksi'], 'a' => 'Hubungkan AI: login web → halaman Koneksi AI → buat token → tempel di konektor MCP AI Anda (URL: ' . '/mcp). Cabut kapan saja dari halaman yang sama.'],
            ['k' => ['withdraw', 'tarik'], 'a' => 'Penarikan via menu Withdraw ke rekening/tujuan tersimpan, diproses admin. MCP tidak menyediakan withdraw.'],
            ['k' => ['promo'], 'a' => 'Lihat promo aktif via tool get_fanzpay_promotions atau halaman Harga.'],
        ];
        $ql = strtolower($q);
        $hits = [];
        foreach ($kb as $e) {
            foreach ($e['k'] as $kw) {
                if (str_contains($ql, $kw)) { $hits[] = $e['a']; break; }
            }
            if (count($hits) >= 3) break;
        }
        if ($hits === []) $hits[] = 'Buka halaman Bantuan/FAQ FanzPay untuk panduan akun, deposit, PPOB, dan keamanan. Siapkan email akun + nomor referensi bila menghubungi CS.';
        return ['answers' => $hits];
    }

    private static function ticket(int $uid, array $a): array {
        $subject = mb_substr(trim((string)($a['subject'] ?? '')), 0, 150);
        $message = mb_substr(trim((string)($a['message'] ?? '')), 0, 1000);
        $ref = mb_substr(trim((string)($a['reference_id'] ?? '')), 0, 64);
        if ($subject === '' || $message === '') throw new McpException(-32602, 'subject dan message wajib diisi.');
        if ($ref !== '') {
            $own = DB::one('SELECT 1 FROM transactions WHERE reference_id = ? AND user_id = ?', [$ref, $uid]);
            if (!$own) throw new McpException(-32602, 'reference_id bukan milik akun ini.');
        }
        $no = 'TKT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        try {
            DB::exec('INSERT INTO mcp_support_tickets (ticket_no, user_id, subject, message, reference_id) VALUES (?,?,?,?,?)', [$no, $uid, $subject, $message, $ref !== '' ? $ref : null]);
        } catch (\Throwable $e) { throw new McpException(-32603, 'Gagal membuat tiket.'); }
        NotificationService::send($uid, 'support', 'Tiket dibuat', $no . ' — ' . mb_substr($subject, 0, 80));
        Logger::audit($uid, 'user', 'mcp.ticket', 'mcp_support_tickets', $no);
        return ['ticket_no' => $no, 'status' => 'open', 'note' => 'Tiket diteruskan ke CS. Tidak ada janji refund/perubahan saldo via AI.'];
    }

    private static function promos(array $a): array {
        $cat = mb_substr(trim((string)($a['category'] ?? '')), 0, 40);
        try {
            $rows = DB::all("SELECT title, body, created_at FROM promos WHERE is_active=1 ORDER BY id DESC LIMIT 20");
        } catch (\Throwable $e) { $rows = []; }
        if ($cat !== '') {
            $rows = array_values(array_filter($rows, fn($r) => str_contains(strtolower((string)($r['title'] ?? '') . ' ' . (string)($r['body'] ?? '')), strtolower($cat))));
        }
        try {
            $banners = DB::all('SELECT title, link FROM banners WHERE is_active=1 ORDER BY sort ASC LIMIT 10');
        } catch (\Throwable $e) { $banners = []; }
        return ['promotions' => $rows, 'banners' => $banners, 'note' => 'Hanya promo aktif. Syarat mengikuti ketentuan tampilan.'];
    }

    private static function reminders(int $uid): array {
        try {
            $rows = DB::all('SELECT title, body, remind_at, is_done FROM mcp_reminders WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$uid]);
        } catch (\Throwable $e) { $rows = []; }
        return ['reminders' => $rows, 'note' => 'Kelola pengingat dari halaman web resmi. AI tidak mengirim notifikasi.'];
    }

    // ---- helpers ----
    private static function productRow(string $code): ?array {
        $p = DB::one('SELECT p.code, p.name, p.description, p.is_featured, c.code cat_code, c.name cat_name, pp.sell_price price FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id WHERE p.code=? AND p.is_active=1 AND c.is_active=1', [$code]);
        return $p ? self::pubProduct($p) : null;
    }

    private static function pubProduct(array $it): array {
        return [
            'code' => (string)($it['code'] ?? ''),
            'name' => (string)($it['name'] ?? ''),
            'description' => mb_substr((string)($it['description'] ?? ''), 0, 255),
            'category' => (string)($it['cat_code'] ?? ''),
            'category_name' => (string)($it['cat_name'] ?? ''),
            'price' => (int)($it['price'] ?? 0),
            'price_rp' => rupiah((int)($it['price'] ?? 0)),
            'featured' => !empty($it['is_featured']),
        ];
    }

    private static function reason(array $it, string $need, int $budget): string {
        $r = [];
        if ($need !== '' && str_contains(strtolower((string)($it['name'] ?? '')), strtolower(mb_substr($need, 0, 20)))) $r[] = 'cocok dengan kebutuhan';
        if ($budget > 0 && (int)($it['price'] ?? 0) <= $budget) $r[] = 'masuk anggaran ' . rupiah($budget);
        if (!empty($it['is_featured'])) $r[] = 'produk unggulan';
        return $r !== [] ? implode('; ', $r) . '.' : 'Sesuai kategori yang diminta.';
    }

    private static function statusHint(string $st): string {
        return match ($st) {
            'success' => 'Berhasil final.',
            'pending', 'processing' => 'Belum final — menunggu provider. Jangan ulangi pembayaran.',
            'refunded', 'reversed' => 'Dana sudah dikembalikan ke saldo.',
            'failed', 'expired' => 'Gagal/kedaluwarsa — tidak ada dana keluar. Silakan buat transaksi baru.',
            default => 'Status: ' . $st,
        };
    }

    private static function maskPhone(string $p): string {
        $len = strlen($p);
        return $len < 6 ? $p : substr($p, 0, 4) . str_repeat('•', $len - 7) . substr($p, -3);
    }
}
