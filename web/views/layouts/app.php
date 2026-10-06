<?php
/** Layout aplikasi user: sidebar desktop + bottom-nav mobile. Glass + theme. */
require_once fp_base_path('views/partials/icons.php');
/** @var string $title */ /** @var string $nav */ /** @var array $user */
$nav = $nav ?? 'home';
$items = [
    ['k' => 'home', 'label' => 'Beranda', 'icon' => 'home', 'href' => url('home')],
    ['k' => 'products', 'label' => 'Produk', 'icon' => 'grid', 'href' => url('produk')],
    ['k' => 'transfer', 'label' => 'Transfer', 'icon' => 'swap', 'href' => url('transfer')],
    ['k' => 'history', 'label' => 'Riwayat', 'icon' => 'clock', 'href' => url('riwayat')],
    ['k' => 'profile', 'label' => 'Profil', 'icon' => 'user', 'href' => url('profil')],
];
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'FanzPay') ?> — FanzPay</title>
<meta name="description" content="Area pelanggan FanzPay: kelola saldo, transfer gratis, deposit QRIS, tarik dana, dan beli produk digital.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#F4F6FB">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="shell">
  <aside class="side" aria-label="Navigasi">
    <a class="brand" href="<?= url('home') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="34" height="34"><span>FanzPay</span></a>
    <nav class="side-nav">
      <?php foreach ($items as $it): ?>
        <a href="<?= $it['href'] ?>" class="<?= $nav === $it['k'] ? 'on' : '' ?>"><?= icon($it['icon'], 19) ?><span><?= e($it['label']) ?></span></a>
      <?php endforeach; ?>
      <a href="<?= url('deposit') ?>" class="<?= $nav === 'deposit' ? 'on' : '' ?>"><?= icon('plus', 19) ?><span>Deposit</span></a>
      <a href="<?= url('withdraw') ?>"><?= icon('card', 19) ?><span>Tarik Dana</span></a>
      <a href="<?= url('qr') ?>"><?= icon('qr', 19) ?><span>QR Saya</span></a>
    </nav>
    <div class="side-foot">
      <a href="<?= url('profil') ?>"><?= icon('user', 18) ?><span><?= e(mb_substr($user['name'] ?? 'Pelanggan', 0, 22)) ?></span></a>
      <a href="<?= url('logout') ?>"><?= icon('out', 18) ?><span>Keluar</span></a>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <span class="top-title"><?= e($title ?? '') ?></span>
      <div class="top-act">
        <button class="theme-toggle" data-theme-toggle aria-label="Mode gelap/terang" style="width:40px;height:40px"><span data-theme-icon>🌙</span></button>
        <a class="icon-btn" href="<?= url('notifikasi') ?>" aria-label="Notifikasi"><?= icon('bell') ?><?php if (($unread ?? 0) > 0): ?><i class="dot"><?= (int)$unread > 9 ? '9+' : (int)$unread ?></i><?php endif; ?></a>
        <a class="me" href="<?= url('profil') ?>" aria-label="Profil"><span><?= e(mb_substr($user['name'] ?? 'U', 0, 1)) ?></span></a>
      </div>
    </header>
    <div class="content anim-fadeUp"><?= $content ?? '' ?></div>
  </div>
</div>
<nav class="bottomnav" aria-label="Navigasi bawah">
  <?php foreach (array_slice($items, 0, 5) as $it): ?>
    <a href="<?= $it['href'] ?>" class="<?= $nav === $it['k'] ? 'on' : '' ?>"><?= icon($it['icon'], 22) ?><small><?= e($it['label']) ?></small></a>
  <?php endforeach; ?>
</nav>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
