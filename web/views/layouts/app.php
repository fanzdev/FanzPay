<?php
/** Layout aplikasi user: sidebar desktop + bottom-nav mobile. Glass + theme. */
require_once fp_base_path('views/partials/icons.php');
/** @var string $title */ /** @var string $nav */ /** @var array $user */ /** @var int $balance */ /** @var int $unread */ /** @var bool $has_pin */
$nav = $nav ?? 'home';
$flat = [
    ['k' => 'home', 'label' => 'Beranda', 'icon' => 'home', 'href' => url('home')],
    ['k' => 'products', 'label' => 'Produk', 'icon' => 'grid', 'href' => url('produk')],
    ['k' => 'transfer', 'label' => 'Transfer', 'icon' => 'swap', 'href' => url('transfer')],
    ['k' => 'history', 'label' => 'Riwayat', 'icon' => 'clock', 'href' => url('riwayat')],
    ['k' => 'profile', 'label' => 'Profil', 'icon' => 'user', 'href' => url('profil')],
];
$finKeys = ['deposit', 'transfer', 'withdraw', 'history'];
$accKeys = ['profile', 'notif'];
$finOpen = in_array($nav, $finKeys, true);
$accOpen = in_array($nav, $accKeys, true);
$__initial = mb_substr($user['name'] ?? 'U', 0, 1);
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
    <a class="brand" href="<?= url('home') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="44" height="44"><span>FanzPay</span></a>
    <div class="side-bal">
      <div><small>Saldo aktif</small><b><?= rupiah((int)($balance ?? 0)) ?></b></div>
      <a href="<?= url('deposit') ?>" aria-label="Deposit"><?= icon('plus', 18) ?></a>
    </div>
    <nav class="side-nav">
      <p class="side-cap">Menu Utama</p>
      <a href="<?= url('home') ?>" class="<?= $nav === 'home' ? 'on' : '' ?>"><span class="side-ic"><?= icon('home', 18) ?></span><span>Beranda</span></a>
      <a href="<?= url('produk') ?>" class="<?= $nav === 'products' ? 'on' : '' ?>"><span class="side-ic"><?= icon('grid', 18) ?></span><span>Produk Digital</span></a>
      <p class="side-cap">Keuangan</p>
      <details class="side-group"<?= $finOpen ? ' open' : '' ?>>
        <summary class="side-group-h<?= $finOpen ? ' on' : '' ?>"><span class="side-ic"><?= icon('wallet', 18) ?></span><span>Transaksi Dana</span><span class="side-chev"><?= icon('chev', 14) ?></span></summary>
        <div class="side-sub">
          <a href="<?= url('deposit') ?>" class="<?= $nav === 'deposit' ? 'on' : '' ?>"><span class="side-ic"><?= icon('qr', 16) ?></span><span>Deposit QRIS</span></a>
          <a href="<?= url('transfer') ?>" class="<?= $nav === 'transfer' ? 'on' : '' ?>"><span class="side-ic"><?= icon('swap', 16) ?></span><span>Transfer Gratis</span></a>
          <a href="<?= url('withdraw') ?>" class="<?= $nav === 'withdraw' ? 'on' : '' ?>"><span class="side-ic"><?= icon('bank', 16) ?></span><span>Tarik Dana</span></a>
          <a href="<?= url('riwayat') ?>" class="<?= $nav === 'history' ? 'on' : '' ?>"><span class="side-ic"><?= icon('receipt', 16) ?></span><span>Riwayat Transaksi</span></a>
        </div>
      </details>
      <p class="side-cap">Akun</p>
      <details class="side-group"<?= $accOpen ? ' open' : '' ?>>
        <summary class="side-group-h<?= $accOpen ? ' on' : '' ?>"><span class="side-ic"><?= icon('user', 18) ?></span><span>Akun Saya</span><span class="side-chev"><?= icon('chev', 14) ?></span></summary>
        <div class="side-sub">
          <a href="<?= url('profil') ?>" class="<?= $nav === 'profile' ? 'on' : '' ?>"><span class="side-ic"><?= icon('user', 16) ?></span><span>Profil</span></a>
          <a href="<?= url('qr') ?>"><span class="side-ic"><?= icon('qr', 16) ?></span><span>QR Saya</span></a>
          <a href="<?= url('pin') ?>"><span class="side-ic"><?= icon('lock', 16) ?></span><span>PIN Transaksi</span><?php if (!($has_pin ?? true)): ?><i class="side-dot"></i><?php endif; ?></a>
          <a href="<?= url('notifikasi') ?>" class="<?= $nav === 'notif' ? 'on' : '' ?>"><span class="side-ic"><?= icon('bell', 16) ?></span><span>Notifikasi</span><?php if (($unread ?? 0) > 0): ?><i class="side-count"><?= (int)$unread > 9 ? '9+' : (int)$unread ?></i><?php endif; ?></a>
        </div>
      </details>
    </nav>
    <div class="side-foot">
      <a class="side-user" href="<?= url('profil') ?>"><span class="side-ava"><?= e(mb_strtoupper(mb_substr($__initial, 0, 1))) ?></span><span class="side-who"><b><?= e(mb_substr($user['name'] ?? 'Pelanggan', 0, 22)) ?></b><small><?= rupiah((int)($balance ?? 0)) ?></small></span></a>
      <a href="<?= url('logout') ?>"><span class="side-ic"><?= icon('out', 17) ?></span><span>Keluar</span></a>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <span class="top-title"><?= e($title ?? '') ?></span>
      <div class="top-act">
        <button class="theme-toggle" data-theme-toggle aria-label="Mode gelap/terang" style="width:40px;height:40px"><span data-theme-icon><?= icon('moon', 18) ?></span></button>
        <a class="icon-btn" href="<?= url('notifikasi') ?>" aria-label="Notifikasi"><?= icon('bell') ?><?php if (($unread ?? 0) > 0): ?><i class="dot"><?= (int)$unread > 9 ? '9+' : (int)$unread ?></i><?php endif; ?></a>
        <a class="me" href="<?= url('profil') ?>" aria-label="Profil"><span><?= e(mb_substr($user['name'] ?? 'U', 0, 1)) ?></span></a>
      </div>
    </header>
    <div class="content anim-fadeUp"><?= $content ?? '' ?></div>
  </div>
</div>
<nav class="bottomnav" aria-label="Navigasi bawah">
  <?php foreach ($flat as $it): ?>
    <a href="<?= $it['href'] ?>" class="<?= $nav === $it['k'] ? 'on' : '' ?>"><?= icon($it['icon'], 22) ?><small><?= e($it['label']) ?></small></a>
  <?php endforeach; ?>
</nav>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
