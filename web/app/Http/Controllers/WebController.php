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
            'title' => 'FanzPay — Dompet Digital & PPOB Indonesia',
            'featured' => $featured,
            'catalog_total' => $catalogTotal,
            'cat_total' => $catTotal,
            'apk' => $apk,
            'min_deposit' => Settings::int('min_deposit', 10000),
            'max_deposit' => Settings::int('max_deposit', 5000000),
            'min_withdraw' => Settings::int('min_withdraw', 20000),
            'min_transfer' => Settings::int('min_transfer', 1000),
            'meta_desc' => 'FanzPay: dompet digital + PPOB Indonesia. Deposit QRIS real-time, transfer gratis, tarik dana, beli pulsa, data, PLN, BPJS, e-money, dan voucher game.',
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
        // Sumber tunggal Q&A: dipakai view (HTML) + layout (FAQPage JSON-LD). Tanpa konten palsu.
        $items = [
            ['q' => 'Apa itu FanzPay?', 'a' => 'FanzPay adalah dompet digital dan layanan PPOB Indonesia. Dengan satu akun kamu bisa menyimpan saldo, transfer ke sesama pengguna tanpa biaya, menarik dana ke bank dan e-wallet, serta membeli pulsa, paket data, token dan tagihan PLN, BPJS, top up e-money, dan voucher game.'],
            ['q' => 'Bagaimana cara mendaftar?', 'a' => 'Buka halaman Registrasi, isi nama lengkap, nomor HP aktif, email aktif, dan kata sandi minimal 8 karakter. Masukkan kode OTP yang dikirim ke email untuk verifikasi, lalu buat PIN 6 digit yang dipakai untuk setiap transaksi sensitif.'],
            ['q' => 'Bagaimana cara mengisi saldo?', 'a' => 'Masuk ke dashboard, buka menu Deposit, masukkan nominal sesuai batas yang tampil, lalu pindai kode QRIS yang dibuat sistem. Saldo masuk otomatis setelah pembayaran terverifikasi oleh provider. Status pending, berhasil, atau kedaluwarsa bisa dipantau di menu Deposit dan Riwayat.'],
            ['q' => 'Bagaimana cara melakukan transaksi?', 'a' => 'Pilih produk di menu Produk dashboard, masukkan nomor atau ID tujuan, periksa nama produk dan harga final, konfirmasi pembayaran dengan PIN 6 digit, lalu periksa status di menu Riwayat dan notifikasi.'],
            ['q' => 'Apa yang terjadi jika transaksi gagal?', 'a' => 'Dana otomatis dikembalikan ke saldo dan status transaksi berubah menjadi refund, gagal, atau reversed. Kamu menerima notifikasi dan dapat melihat referensi pengembalian di Riwayat. Tidak ada dana yang hilang sepihak.'],
            ['q' => 'Berapa lama proses transaksi?', 'a' => 'Sebagian besar pembelian PPOB selesai dalam hitungan detik hingga beberapa menit mengikuti respons provider. Jika status masih pending atau processing, tunggu notifikasi berikutnya dan hindari mengulang pembayaran yang sama.'],
            ['q' => 'Bagaimana cara transfer saldo?', 'a' => 'Buka menu Transfer, masukkan nomor HP penerima atau pindai QR Code-nya, isi nominal, lalu konfirmasi dengan PIN. Transfer antar pengguna gratis (Rp0) dan tercatat di Riwayat kedua belah pihak.'],
            ['q' => 'Bagaimana cara mengamankan akun?', 'a' => 'Gunakan kata sandi unik, rahasiakan PIN 6 digit, pastikan email terverifikasi, aktifkan kunci layar perangkat, dan selalu keluar dari dashboard di perangkat bersama. Segera ganti kata sandi jika ada aktivitas yang tidak dikenal.'],
            ['q' => 'Bagaimana cara menghubungi customer service?', 'a' => 'Buka halaman Pusat Bantuan untuk panduan mandiri dan kanal kontak resmi. Siapkan email akun, nomor HP, dan nomor referensi transaksi agar penanganan lebih cepat.'],
        ];
        View::render('faq', [
            'title' => 'FAQ — Pertanyaan Umum FanzPay',
            'meta_desc' => 'Jawaban atas pertanyaan umum FanzPay: pendaftaran, isi saldo, transaksi, transaksi gagal, transfer, keamanan akun, dan customer service.',
            'faq_items' => $items,
            'faq_schema' => $items,
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

    /** Halaman publik pay/{public_id}: tampilkan nama penerima (tanpa data sensitif). Halaman per-user = noindex. */
    public function pay(string $publicId): void {
        $t = DB::one('SELECT name, public_id FROM users WHERE public_id = ? AND role = "user" AND status = ?', [$publicId, 'active']);
        if (!$t) { http_response_code(404); View::render('errors/404', ['title' => 'Tidak ditemukan', 'robots' => 'noindex, nofollow'], 'public'); return; }
        View::render('pay', ['title' => 'Kirim ke ' . $t['name'], 'to' => $t, 'robots' => 'noindex, nofollow'], 'public');
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
        // SEO: /unduh adalah alias — permanen ke URL kanonis agar tidak ada konten ganda.
        $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $bp = app_base()['path'];
        if ($bp !== '' && str_starts_with($reqPath, $bp)) $reqPath = substr($reqPath, strlen($bp));
        if (rtrim($reqPath, '/') === '/unduh') {
            $qs = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
            redirect('download-apk' . ($qs !== '' ? '?' . $qs : ''), 301);
        }
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
        View::render('download', ['title' => 'Unduh Aplikasi Android FanzPay', 'apk' => $apk, 'meta_desc' => 'Unduh aplikasi Android resmi FanzPay (APK): deposit QRIS real-time, transfer gratis, beli pulsa, data, PLN, dan voucher game dalam satu aplikasi.'], 'public');
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

    /** Robots.txt untuk crawler: halaman privat & per-user ditutup agar crawl budget fokus ke konten. */
    public function robots(): void {
        $base = rtrim((string)config('app.url', url('')), '/');
        header('Content-Type: text/plain; charset=utf-8');
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /webhooks/',
            'Disallow: /home',
            'Disallow: /dashboard',
            'Disallow: /produk',
            'Disallow: /transfer',
            'Disallow: /deposit',
            'Disallow: /withdraw',
            'Disallow: /riwayat/',
            'Disallow: /notifikasi',
            'Disallow: /profil',
            'Disallow: /pin',
            'Disallow: /qr',
            'Disallow: /verify',
            'Disallow: /logout',
            'Disallow: /pay/',
            'Disallow: /setup-super',
            'Sitemap: ' . $base . '/sitemap.xml',
        ];
        echo implode("\n", $lines) . "\n";
        exit;
    }
}
