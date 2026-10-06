<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\View;
use App\Services\PricingService;

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
            'meta_desc' => 'FanzPay adalah dompet digital + PPOB: deposit QRIS real-time, transfer gratis antar pengguna, tarik dana ke bank & e-wallet, beli pulsa, data, PLN, e-money, dan voucher game. Transparan, aman, tanpa biaya tersembunyi.',
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
