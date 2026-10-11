<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\PricingService;
use App\Services\Settings;

/** Halaman publik: landing + bayar via QR user + unduhan APK + SEO. */
final class WebController {
    public function landing(): void {
        $u = Auth::user();
        if ($u) { redirect($u['role'] === 'user' ? '' : 'admin'); }
        // ANTI DATA DEMO: hanya tampilkan katalog REAL dari database/provider.
        // Jika katalog kosong (belum sync), tampilkan empty-state jujur — tanpa angka/nama palsu.
        $featured = [];
        $catalogTotal = 0;
        $catTotal = 0;
        try {
            $featured = array_slice(PricingService::catalog(), 0, 6);
            $catalogTotal = (int)(DB::one('SELECT COUNT(*) c FROM products WHERE is_active=1')['c'] ?? 0);
            $catTotal = (int)(DB::one('SELECT COUNT(*) c FROM product_categories WHERE is_active=1')['c'] ?? 0);
        } catch (\Throwable $e) { $featured = []; }
        $apk = $this->apkInfo();
        View::render('landing', [
            'title' => 'FanzPay — Dompet Digital & PPOB untuk Generasi Bergerak Cepat',
            'featured' => $featured,
            'catalog_total' => $catalogTotal,
            'cat_total' => $catTotal,
            'apk' => $apk,
            'min_deposit' => Settings::int('min_deposit', 10000),
            'max_deposit' => Settings::int('max_deposit', 5000000),
            'min_withdraw' => Settings::int('min_withdraw', 20000),
            'min_transfer' => Settings::int('min_transfer', 1000),
            'meta_desc' => 'FanzPay adalah dompet digital + PPOB: deposit QRIS real-time, transfer gratis antar pengguna, tarik dana ke bank & e-wallet, beli pulsa, data, PLN, e-money, dan voucher game. Transparan, aman, tanpa biaya tersembunyi.',
        ], 'public');
    }

    /** GET /layanan — daftar layanan PPOB (kategori real). */
    public function layanan(): void {
        $cats = [];
        $total = 0;
        try {
            $cats = DB::all('SELECT code, name, icon FROM product_categories WHERE is_active=1 ORDER BY sort ASC');
            $total = (int)(DB::one('SELECT COUNT(*) c FROM products WHERE is_active=1')['c'] ?? 0);
        } catch (\Throwable $e) { $cats = []; }
        View::render('layanan', [
            'title' => 'Layanan PPOB FanzPay — Pulsa, Data, PLN, BPJS, E-Wallet, Game',
            'categories' => $cats,
            'catalog_total' => $total,
            'meta_desc' => 'Daftar layanan FanzPay: pulsa, paket data, token dan tagihan PLN, BPJS, top up e-wallet, voucher game, TV, PDAM, dan produk digital lainnya.',
        ], 'public');
    }

    /** GET /cara-kerja — halaman mandiri Cara Kerja. */
    public function caraKerja(): void {
        View::render('cara-kerja', [
            'title' => 'Cara Kerja FanzPay — 5 Langkah Transaksi',
            'meta_desc' => 'Cara kerja FanzPay: registrasi akun, pilih produk, masukkan nomor tujuan, lakukan pembayaran dengan PIN, dan periksa status transaksi.',
        ], 'public');
    }

    /** GET /keunggulan — halaman mandiri Keunggulan. */
    public function keunggulan(): void {
        View::render('keunggulan', [
            'title' => 'Keunggulan FanzPay — 8 Fitur Unggulan',
            'meta_desc' => 'Keunggulan FanzPay: satu platform multi-kebutuhan, harga transparan, riwayat lengkap, notifikasi, pengelolaan saldo, transfer gratis, QR Code, dan tampilan responsif.',
        ], 'public');
    }

    /** GET /harga — informasi harga dan biaya transaksi. */
    public function harga(): void {
        View::render('harga', [
            'title' => 'Harga dan Biaya Transaksi FanzPay',
            'min_deposit' => Settings::int('min_deposit', 10000),
            'max_deposit' => Settings::int('max_deposit', 5000000),
            'min_withdraw' => Settings::int('min_withdraw', 20000),
            'max_withdraw' => Settings::int('max_withdraw', 5000000),
            'min_transfer' => Settings::int('min_transfer', 1000),
            'max_transfer' => Settings::int('max_transfer', 10000000),
            'meta_desc' => 'Informasi harga produk, biaya administrasi, ketentuan deposit, penarikan saldo, dan kebijakan pengembalian dana FanzPay.',
        ], 'public');
    }

    /** GET /faq — pertanyaan umum. */
    public function faq(): void {
        View::render('faq', [
            'title' => 'FAQ — Pertanyaan Umum FanzPay',
            'meta_desc' => 'Jawaban atas pertanyaan umum FanzPay: pendaftaran, isi saldo, transaksi, transaksi gagal, transfer, keamanan akun, dan customer service.',
        ], 'public');
    }

    /** GET /bantuan — pusat bantuan. */
    public function bantuan(): void {
        View::render('bantuan', [
            'title' => 'Pusat Bantuan FanzPay',
            'meta_desc' => 'Pusat bantuan FanzPay: panduan akun, deposit QRIS, transfer, PPOB, penarikan saldo, kendala refund, dan kontak customer service.',
        ], 'public');
    }

    /** GET /syarat-ketentuan */
    public function syarat(): void {
        View::render('syarat-ketentuan', [
            'title' => 'Syarat dan Ketentuan FanzPay',
            'meta_desc' => 'Syarat dan ketentuan penggunaan dompet digital dan layanan PPOB FanzPay.',
        ], 'public');
    }

    /** GET /kebijakan-privasi */
    public function privasi(): void {
        View::render('kebijakan-privasi', [
            'title' => 'Kebijakan Privasi FanzPay',
            'meta_desc' => 'Kebijakan privasi FanzPay: data yang dikumpulkan, penggunaan, keamanan, dan hak pengguna.',
        ], 'public');
    }

    /** GET /kebijakan-refund */
    public function refund(): void {
        View::render('kebijakan-refund', [
            'title' => 'Kebijakan Refund FanzPay',
            'meta_desc' => 'Kebijakan pengembalian dana FanzPay untuk transaksi gagal, deposit, dan penarikan.',
        ], 'public');
    }

    /** Halaman publik pay/{public_id}: tampilkan nama penerima (tanpa data sensitif). */
    public function pay(string $publicId): void {
        $t = DB::one('SELECT name, public_id FROM users WHERE public_id = ? AND role = "user" AND status = ?', [$publicId, 'active']);
        if (!$t) { http_response_code(404); View::render('errors/404', ['title' => 'Tidak ditemukan'], 'public'); return; }
        View::render('pay', ['title' => 'Kirim ke ' . $t['name'], 'to' => $t], 'public');
    }

    /** Info file APK Android (tanpa data palsu: ukuran & versi dibaca dari file asli bila ada). */
    private function apkInfo(): array {
        $file = fp_base_path('public/assets/apk/fanzpay.apk');
        if (is_file($file)) {
            $size = filesize($file) ?: 0;
            return [
                'exists' => true, 'size' => $size,
                'size_human' => $size > 0 ? number_format($size / 1048576, 1, ',', '.') . ' MB' : '—',
                'version' => (string)@filemtime($file),
                'url' => url('download-apk'),
            ];
        }
        return ['exists' => false, 'size' => 0, 'size_human' => '', 'version' => '', 'url' => url('download-apk')];
    }

    /** GET /download-apk — unduh file Android bila sudah diunggah admin, jika belum tampilkan panduan build. */
    public function downloadApk(): void {
        $file = fp_base_path('public/assets/apk/fanzpay.apk');
        if (is_file($file) && isset($_GET['go'])) {
            $name = 'fanzpay-' . date('Ymd', (int)filemtime($file)) . '.apk';
            header('Content-Type: application/vnd.android.package-archive');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: public, max-age=3600');
            readfile($file);
            exit;
        }
        $apk = $this->apkInfo();
        if (is_file($file)) {
            // Langsung kirim file saat dibuka (UX 1-klik), browser tetap bisa kembali.
            $dl = isset($_GET['info']);
            if (!$dl) {
                $name = 'fanzpay-' . date('Ymd', (int)filemtime($file)) . '.apk';
                header('Content-Type: application/vnd.android.package-archive');
                header('Content-Disposition: attachment; filename="' . $name . '"');
                header('Content-Length: ' . filesize($file));
                header('Cache-Control: public, max-age=3600');
                readfile($file);
                exit;
            }
        }
        View::render('download', ['title' => 'Unduh Aplikasi Android FanzPay', 'apk' => $apk], 'public');
    }

    /** Sitemap XML dinamis untuk SEO. */
    public function sitemap(): void {
        $base = rtrim((string)config('app.url', url('')), '/');
        if ($base === '') $base = url('');
        $today = date('Y-m-d');
        $urls = [
            ['loc' => $base . '/', 'freq' => 'daily', 'pr' => '1.0'],
            ['loc' => $base . '/layanan', 'freq' => 'weekly', 'pr' => '0.8'],
            ['loc' => $base . '/cara-kerja', 'freq' => 'weekly', 'pr' => '0.7'],
            ['loc' => $base . '/keunggulan', 'freq' => 'weekly', 'pr' => '0.7'],
            ['loc' => $base . '/harga', 'freq' => 'weekly', 'pr' => '0.7'],
            ['loc' => $base . '/faq', 'freq' => 'weekly', 'pr' => '0.7'],
            ['loc' => $base . '/bantuan', 'freq' => 'weekly', 'pr' => '0.7'],
            ['loc' => $base . '/syarat-ketentuan', 'freq' => 'monthly', 'pr' => '0.4'],
            ['loc' => $base . '/kebijakan-privasi', 'freq' => 'monthly', 'pr' => '0.4'],
            ['loc' => $base . '/kebijakan-refund', 'freq' => 'monthly', 'pr' => '0.4'],
            ['loc' => $base . '/login', 'freq' => 'monthly', 'pr' => '0.6'],
            ['loc' => $base . '/register', 'freq' => 'monthly', 'pr' => '0.7'],
            ['loc' => $base . '/download-apk', 'freq' => 'weekly', 'pr' => '0.8'],
        ];
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $x) {
            echo '  <url><loc>' . e($x['loc']) . '</loc><lastmod>' . $today . '</lastmod><changefreq>' . $x['freq'] . '</changefreq><priority>' . $x['pr'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
        exit;
    }

    /** Robots.txt untuk crawler. */
    public function robots(): void {
        $base = rtrim((string)config('app.url', url('')), '/');
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nAllow: /\nAllow: /download-apk\nDisallow: /admin\nDisallow: /api/\nDisallow: /deposit/\nDisallow: /riwayat/\nSitemap: " . $base . "/sitemap.xml\n";
        exit;
    }
}
