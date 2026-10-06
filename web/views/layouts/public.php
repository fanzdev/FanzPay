<?php
/** Layout publik: landing, login info, pay, download, error. Full SEO + glass + theme. */
/** @var string $title */ /** @var string|null $meta_desc */
require_once fp_base_path('views/partials/icons.php');
$__desc = $meta_desc ?? 'FanzPay — dompet digital & PPOB untuk generasi bergerak cepat. Deposit QRIS real-time, transfer gratis antar pengguna, tarik dana ke bank & e-wallet, beli pulsa, data, PLN, e-money, voucher game. Transparan, aman, tanpa biaya tersembunyi.';
$__base = rtrim((string)config('app.url', url('')), '/');
if ($__base === '') $__base = url('');
$__canon = $__base . '/';
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'FanzPay — Dompet Digital & PPOB untuk Generasi Bergerak Cepat') ?></title>
<meta name="description" content="<?= e($__desc) ?>">
<meta name="keywords" content="fanzpay, dompet digital, e-wallet indonesia, ppob, pulsa murah, paket data, token pln, voucher game, qris, transfer gratis, top up e-money">
<meta name="author" content="FanzPay">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= e($__canon) ?>">
<meta name="theme-color" content="#F4F6FB">
<meta property="og:type" content="website">
<meta property="og:site_name" content="FanzPay">
<meta property="og:title" content="<?= e($title ?? 'FanzPay') ?>">
<meta property="og:description" content="<?= e($__desc) ?>">
<meta property="og:url" content="<?= e($__canon) ?>">
<meta property="og:image" content="<?= asset('img/logo.webp') ?>">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title ?? 'FanzPay') ?>">
<meta name="twitter:description" content="<?= e($__desc) ?>">
<meta name="twitter:image" content="<?= asset('img/logo.webp') ?>">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Organization","name":"FanzPay","url":"<?= e($__base) ?>/","logo":"<?= asset('img/logo.webp') ?>","sameAs":[],"description":"<?= e($__desc) ?>"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebSite","name":"FanzPay","url":"<?= e($__base) ?>/","inLanguage":"id"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"MobileApplication","name":"FanzPay Android","operatingSystem":"Android","applicationCategory":"FinanceApplication","offers":{"@type":"Offer","price":"0","priceCurrency":"IDR"}}
</script>
</head>
<body>
<a href="#main" style="position:absolute;left:-999px">Lewati ke konten</a>
<header class="site-nav">
  <div class="wrap nav-in">
    <a class="brand" href="<?= url('') ?>" aria-label="FanzPay beranda">
      <img src="<?= asset('img/logo.webp') ?>" alt="Logo FanzPay" width="36" height="36" fetchpriority="high">
      <span>FanzPay</span>
    </a>
    <nav class="nav-links" aria-label="Navigasi utama">
      <a href="<?= url('') ?>#produk">Produk</a>
      <a href="<?= url('') ?>#fitur">Kenapa FanzPay</a>
      <a href="<?= url('') ?>#filosofi">Filosofi</a>
      <a href="<?= url('') ?>#cara">Cara mulai</a>
      <a href="<?= url('download-apk') ?>">Unduh APK</a>
    </nav>
    <div class="nav-cta">
      <button class="theme-toggle" data-theme-toggle aria-label="Ganti mode gelap/terang"><span data-theme-icon>🌙</span></button>
      <a class="btn btn-ghost" href="<?= url('login') ?>">Masuk</a>
      <a class="btn btn-primary" href="<?= url('register') ?>">Daftar gratis</a>
      <button class="hamburger" data-menu-toggle="#mmenu" aria-label="Buka menu">☰</button>
    </div>
  </div>
  <nav class="mobile-menu" id="mmenu" aria-label="Menu seluler">
    <a href="<?= url('') ?>#produk">Produk</a>
    <a href="<?= url('') ?>#fitur">Kenapa FanzPay</a>
    <a href="<?= url('') ?>#filosofi">Filosofi</a>
    <a href="<?= url('') ?>#cara">Cara mulai</a>
    <a href="<?= url('download-apk') ?>">⬇ Unduh Aplikasi Android</a>
    <a href="<?= url('login') ?>">Masuk</a>
    <a href="<?= url('register') ?>">Daftar gratis</a>
  </nav>
</header>
<main id="main"><?= $content ?? '' ?></main>
<footer class="site-foot">
  <div class="wrap foot-in">
    <div class="foot-brand">
      <a class="brand" href="<?= url('') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="30" height="30"><span>FanzPay</span></a>
      <p style="margin-top:12px">Uangmu, ceritamu. FanzPay adalah dompet digital + PPOB yang tenang di depan, serius di belakang: ledger yang bisa diaudit, webhook terverifikasi, tanpa biaya tersembunyi. Dibangun untuk generasi yang bergerak cepat tapi tetap mikir.</p>
      <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
        <a class="btn btn-apk btn-sm" href="<?= url('download-apk') ?>">⬇ Unduh APK Android</a>
        <a class="btn btn-ghost btn-sm" href="<?= url('register') ?>">Buat akun gratis</a>
      </div>
    </div>
    <div class="foot-col">
      <h4>Produk</h4>
      <a href="<?= url('login') ?>">Pulsa &amp; Paket Data</a>
      <a href="<?= url('login') ?>">Token &amp; Tagihan PLN</a>
      <a href="<?= url('login') ?>">E-Money &amp; Voucher Game</a>
      <a href="<?= url('') ?>#produk">Lihat katalog</a>
    </div>
    <div class="foot-col">
      <h4>Akses</h4>
      <a href="<?= url('login') ?>">Masuk — semua peran</a>
      <a href="<?= url('register') ?>">Daftar pelanggan</a>
      <a href="<?= url('setup-super') ?>">Setup Super Admin</a>
      <a href="<?= url('download-apk') ?>">Unduh Android</a>
    </div>
    <div class="foot-col">
      <h4>Transparansi</h4>
      <a href="<?= url('sitemap.xml') ?>">Sitemap</a>
      <a href="<?= url('robots.txt') ?>">Robots</a>
      <a href="<?= url('login') ?>">Transfer gratis antar pengguna</a>
      <a href="<?= url('login') ?>">Deposit QRIS real-time</a>
    </div>
  </div>
  <div class="wrap foot-base">
    <small>© <?= date('Y') ?> FanzPay · Pelan tapi pasti. Seluruh transaksi tercatat di ledger yang dapat diaudit.</small>
    <small>#AntiRibet · #TransparanTanpaDrama</small>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
