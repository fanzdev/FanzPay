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
<meta property="og:image" content="<?= asset('img/ambassador.webp') ?>">
<meta property="og:locale" content="id_ID">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title ?? 'FanzPay') ?>">
<meta name="twitter:description" content="<?= e($__desc) ?>">
<meta name="twitter:image" content="<?= asset('img/ambassador.webp') ?>">
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
      <a href="<?= url('') ?>">Beranda</a>
      <a href="<?= url('layanan') ?>">Layanan</a>
      <a href="<?= url('cara-kerja') ?>">Cara Kerja</a>
      <a href="<?= url('keunggulan') ?>">Keunggulan</a>
      <a href="<?= url('faq') ?>">FAQ</a>
    </nav>
    <div class="nav-cta">
      <button class="theme-toggle" data-theme-toggle aria-label="Ganti mode gelap atau terang"><span data-theme-icon><?= icon('moon', 19) ?></span></button>
      <a class="btn btn-ghost" href="<?= url('login') ?>">Masuk</a>
      <a class="btn btn-primary" href="<?= url('register') ?>">Daftar Sekarang</a>
      <button class="hamburger" data-menu-toggle="#mmenu" aria-label="Buka menu"><?= icon('menu', 20) ?></button>
    </div>
  </div>
  <nav class="mobile-menu" id="mmenu" aria-label="Menu seluler">
    <a href="<?= url('') ?>">Beranda</a>
    <a href="<?= url('layanan') ?>">Layanan</a>
    <a href="<?= url('cara-kerja') ?>">Cara Kerja</a>
    <a href="<?= url('keunggulan') ?>">Keunggulan</a>
    <a href="<?= url('faq') ?>">FAQ</a>
    <a href="<?= url('harga') ?>">Harga dan Biaya</a>
    <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>
    <a href="<?= url('download-apk') ?>">Unduh Aplikasi Android</a>
    <a href="<?= url('login') ?>">Masuk</a>
    <a href="<?= url('register') ?>">Daftar Sekarang</a>
  </nav>
</header>
<main id="main"><?= $content ?? '' ?></main>
<footer class="site-foot">
  <div class="wrap foot-in">
    <div class="foot-brand">
      <a class="brand" href="<?= url('') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="32" height="32" loading="lazy"><span>FanzPay</span></a>
      <p class="foot-desc">FanzPay adalah dompet digital dan layanan PPOB Indonesia: deposit QRIS real-time, transfer gratis antar pengguna, tarik dana ke bank dan e-wallet, serta pembelian pulsa, paket data, listrik PLN, BPJS, e-money, dan voucher game. Setiap transaksi tercatat di ledger yang dapat diaudit.</p>
      <div class="foot-cs">
        <?= icon('headset', 18) ?>
        <span>Butuh bantuan? Kunjungi <a href="<?= url('bantuan') ?>">Pusat Bantuan</a> atau hubungi customer service melalui aplikasi setelah masuk.</span>
      </div>
    </div>
    <div class="foot-col">
      <h4>Navigasi Website</h4>
      <a href="<?= url('') ?>">Beranda</a>
      <a href="<?= url('layanan') ?>">Layanan</a>
      <a href="<?= url('cara-kerja') ?>">Cara Kerja</a>
      <a href="<?= url('keunggulan') ?>">Keunggulan</a>
      <a href="<?= url('harga') ?>">Harga dan Biaya</a>
      <a href="<?= url('faq') ?>">FAQ</a>
      <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>
    </div>
    <div class="foot-col">
      <h4>Layanan</h4>
      <a href="<?= url('layanan') ?>#pulsa">Pulsa</a>
      <a href="<?= url('layanan') ?>#data">Paket Data</a>
      <a href="<?= url('layanan') ?>#pln">Token dan Tagihan PLN</a>
      <a href="<?= url('layanan') ?>#bpjs">BPJS</a>
      <a href="<?= url('layanan') ?>#emoney">Top Up E-Wallet</a>
      <a href="<?= url('layanan') ?>#game">Voucher Game</a>
    </div>
    <div class="foot-col">
      <h4>Akun</h4>
      <a href="<?= url('login') ?>">Masuk</a>
      <a href="<?= url('register') ?>">Daftar Sekarang</a>
      <a href="<?= url('dashboard') ?>">Dashboard Pengguna</a>
      <a href="<?= url('download-apk') ?>">Unduh Aplikasi Android</a>
    </div>
    <div class="foot-col">
      <h4>Legal dan Perusahaan</h4>
      <a href="<?= url('syarat-ketentuan') ?>">Syarat dan Ketentuan</a>
      <a href="<?= url('kebijakan-privasi') ?>">Kebijakan Privasi</a>
      <a href="<?= url('kebijakan-refund') ?>">Kebijakan Refund</a>
      <a href="<?= url('bantuan') ?>#tentang">Tentang FanzPay</a>
    </div>
  </div>
  <div class="wrap foot-base">
    <small>© <?= date('Y') ?> FanzPay. Seluruh hak cipta dilindungi. Transaksi dicatat di ledger yang dapat diaudit.</small>
    <small>Dompet digital dan PPOB Indonesia.</small>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
