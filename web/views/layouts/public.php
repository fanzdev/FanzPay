<?php
/** Layout publik: landing, login info, pay, download, error. Full SEO + glass + theme. */
/** @var string $title */ /** @var string|null $meta_desc */ /** @var string|null $robots */ /** @var array|null $faq_schema */
require_once fp_base_path('views/partials/icons.php');
$__desc = $meta_desc ?? 'FanzPay — dompet digital & PPOB Indonesia: deposit QRIS, transfer gratis, tarik dana, pulsa, data, PLN, e-money, dan voucher game. Aman dan transparan.';
$__base = rtrim((string)config('app.url', url('')), '/');
if ($__base === '') $__base = url('');
$__reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$__bp = app_base()['path'];
if ($__bp !== '' && str_starts_with($__reqPath, $__bp)) $__reqPath = substr($__reqPath, strlen($__bp));
$__canonPath = '/' . trim($__reqPath, '/');
if ($__canonPath === '/index.php') $__canonPath = '/';
$__canon = $__base . ($__canonPath === '/' ? '/' : $__canonPath);
$__robots = $robots ?? 'index, follow, max-image-preview:large';
$__path = $__canonPath;
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'FanzPay — Dompet Digital & PPOB Indonesia') ?></title>
<meta name="description" content="<?= e($__desc) ?>">
<meta name="author" content="FanzPay">
<meta name="robots" content="<?= e($__robots) ?>">
<link rel="canonical" href="<?= e($__canon) ?>">
<meta name="theme-color" content="#F4F6FB">
<meta property="og:type" content="website">
<meta property="og:site_name" content="FanzPay">
<meta property="og:title" content="<?= e($title ?? 'FanzPay') ?>">
<meta property="og:description" content="<?= e($__desc) ?>">
<meta property="og:url" content="<?= e($__canon) ?>">
<meta property="og:image" content="<?= asset('img/ambassador.webp') ?>">
<meta property="og:image:width" content="688">
<meta property="og:image:height" content="1024">
<meta property="og:image:alt" content="Brand ambassador FanzPay">
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
<?php if (!empty($faq_schema) && is_array($faq_schema)):
$__faqEnt = [];
foreach ($faq_schema as $__fq) {
  if (empty($__fq['q']) || empty($__fq['a'])) continue;
  $__faqEnt[] = ['@type' => 'Question', 'name' => (string)$__fq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string)$__fq['a']]];
}
if ($__faqEnt): ?>
<script type="application/ld+json">
<?= json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $__faqEnt], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
<?php endif; endif; ?>
</head>
<body>
<a href="#main" style="position:absolute;left:-999px">Lewati ke konten</a>
<?php
$__nav = [
  ['href' => url(''), 'label' => 'Beranda'],
  ['href' => url('layanan'), 'label' => 'Layanan'],
  ['href' => url('cara-kerja'), 'label' => 'Cara Kerja'],
  ['href' => url('keunggulan'), 'label' => 'Keunggulan'],
  ['href' => url('faq'), 'label' => 'FAQ'],
];
$__norm = rtrim($__path, '/'); if ($__norm === '') $__norm = '/';
?>
<header class="site-nav">
  <div class="wrap nav-in">
    <a class="brand" href="<?= url('') ?>" aria-label="FanzPay beranda">
      <img src="<?= asset('img/logo.webp') ?>" alt="Logo FanzPay" width="42" height="42" fetchpriority="high">
      <span>FanzPay</span>
    </a>
    <nav class="nav-links" aria-label="Navigasi utama">
      <?php foreach ($__nav as $n):
        $frag = ['Beranda' => '', 'Layanan' => '/layanan', 'Cara Kerja' => '/cara-kerja', 'Keunggulan' => '/keunggulan', 'FAQ' => '/faq'][$n['label']] ?? '';
        $on = $frag === '' ? !str_contains($__norm, '/layanan') && !str_contains($__norm, '/cara-kerja') && !str_contains($__norm, '/keunggulan') && !str_contains($__norm, '/faq') && !str_contains($__norm, '/harga') && !str_contains($__norm, '/bantuan') && !str_contains($__norm, '/login') && !str_contains($__norm, '/register') : str_contains($__norm, $frag);
      ?>
      <a href="<?= e($n['href']) ?>" class="<?= $on ? 'on' : '' ?>" <?= $on ? 'aria-current="page"' : '' ?>><?= e($n['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-cta">
      <button type="button" class="theme-toggle" data-theme-toggle aria-label="Ganti mode gelap atau terang"><span data-theme-icon><?= icon('moon', 18) ?></span></button>
      <a class="nav-login" href="<?= url('login') ?>">Masuk</a>
      <a class="btn btn-primary btn-sm" href="<?= url('register') ?>">Daftar</a>
      <button type="button" class="hamburger" data-menu-toggle="#mmenu" aria-label="Buka menu" aria-expanded="false"><?= icon('menu', 20) ?></button>
    </div>
  </div>
  <nav class="mobile-menu" id="mmenu" aria-label="Menu seluler">
    <?php
    $__mlinks = [
      ['href' => url(''), 'label' => 'Beranda', 'frag' => ''],
      ['href' => url('layanan'), 'label' => 'Layanan', 'frag' => '/layanan'],
      ['href' => url('cara-kerja'), 'label' => 'Cara Kerja', 'frag' => '/cara-kerja'],
      ['href' => url('keunggulan'), 'label' => 'Keunggulan', 'frag' => '/keunggulan'],
      ['href' => url('faq'), 'label' => 'FAQ', 'frag' => '/faq'],
      ['href' => url('harga'), 'label' => 'Harga dan Biaya', 'frag' => '/harga'],
      ['href' => url('bantuan'), 'label' => 'Pusat Bantuan', 'frag' => '/bantuan'],
      ['href' => url('login'), 'label' => 'Masuk', 'frag' => '/login'],
      ['href' => url('register'), 'label' => 'Daftar', 'frag' => '/register'],
    ];
    foreach ($__mlinks as $ml):
      $mon = $ml['frag'] === ''
        ? !str_contains($__norm, '/layanan') && !str_contains($__norm, '/cara-kerja') && !str_contains($__norm, '/keunggulan') && !str_contains($__norm, '/faq') && !str_contains($__norm, '/harga') && !str_contains($__norm, '/bantuan') && !str_contains($__norm, '/login') && !str_contains($__norm, '/register')
        : str_contains($__norm, $ml['frag']);
    ?>
    <a href="<?= e($ml['href']) ?>" class="<?= $mon ? 'on' : '' ?>" <?= $mon ? 'aria-current="page"' : '' ?>><?= e($ml['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main id="main"><?= $content ?? '' ?></main>
<footer class="site-foot">
  <div class="wrap foot-in">
    <div class="foot-brand">
      <a class="brand" href="<?= url('') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="40" height="40" loading="lazy"><span>FanzPay</span></a>
      <p class="foot-desc">Dompet digital &amp; PPOB Indonesia. Deposit QRIS, transfer gratis, tarik dana, dan 8 layanan dalam satu saldo.</p>
    </div>
    <div class="foot-col">
      <h4>Navigasi</h4>
      <a href="<?= url('') ?>">Beranda</a>
      <a href="<?= url('layanan') ?>">Layanan</a>
      <a href="<?= url('cara-kerja') ?>">Cara Kerja</a>
      <a href="<?= url('keunggulan') ?>">Keunggulan</a>
      <a href="<?= url('faq') ?>">FAQ</a>
    </div>
    <div class="foot-col">
      <h4>Layanan</h4>
      <a href="<?= url('layanan') ?>#pulsa">Pulsa</a>
      <a href="<?= url('layanan') ?>#data">Paket Data</a>
      <a href="<?= url('layanan') ?>#pln">PLN</a>
      <a href="<?= url('layanan') ?>#bpjs">BPJS</a>
      <a href="<?= url('layanan') ?>#game">Voucher Game</a>
    </div>
    <div class="foot-col">
      <h4>Bantuan</h4>
      <a href="<?= url('harga') ?>">Harga dan Biaya</a>
      <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>
      <a href="<?= url('syarat-ketentuan') ?>">Syarat &amp; Ketentuan</a>
      <a href="<?= url('kebijakan-privasi') ?>">Kebijakan Privasi</a>
      <a href="<?= url('kebijakan-refund') ?>">Kebijakan Refund</a>
    </div>
  </div>
  <div class="wrap foot-base">
    <small>© <?= date('Y') ?> FanzPay. Seluruh hak cipta dilindungi.</small>
    <small><a href="<?= url('login') ?>">Masuk</a> · <a href="<?= url('register') ?>">Daftar</a> · <a href="<?= url('download-apk') ?>">Unduh Android</a></small>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
