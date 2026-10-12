<?php
/** Layout panel admin: sidebar navy + topbar glass + theme toggle. */
require_once fp_base_path('views/partials/icons.php');
/** @var string $title */ /** @var string $nav */ /** @var array $user */ /** @var bool $is_super */
$nav = $nav ?? 'dashboard';
$menu = [
    ['k' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart', 'href' => url('admin')],
    ['k' => 'users', 'label' => 'Pengguna', 'icon' => 'user', 'href' => url('admin/pengguna')],
    ['k' => 'tx', 'label' => 'Transaksi', 'icon' => 'clock', 'href' => url('admin/transaksi')],
    ['k' => 'deposits', 'label' => 'Deposit', 'icon' => 'plus', 'href' => url('admin/deposit')],
    ['k' => 'withdrawals', 'label' => 'Withdrawal', 'icon' => 'card', 'href' => url('admin/withdraw')],
    ['k' => 'products', 'label' => 'Produk', 'icon' => 'grid', 'href' => url('admin/produk')],
    ['k' => 'reports', 'label' => 'Laporan', 'icon' => 'inbox', 'href' => url('admin/laporan')],
    ['k' => 'reconcile', 'label' => 'Rekonsiliasi', 'icon' => 'swap', 'href' => url('admin/rekonsiliasi')],
    ['k' => 'admins', 'label' => 'Admin', 'icon' => 'shield', 'href' => url('admin/admin')],
    ['k' => 'notifications', 'label' => 'Notifikasi', 'icon' => 'bell', 'href' => url('admin/notifikasi')],
    ['k' => 'audit', 'label' => 'Audit Log', 'icon' => 'search', 'href' => url('admin/audit')],
    ['k' => 'settings', 'label' => 'Pengaturan', 'icon' => 'gear', 'href' => url('admin/pengaturan')],
    ['k' => 'apiconfig', 'label' => 'API Config', 'icon' => 'zap', 'href' => url('admin/api')],
];
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'Admin') ?> — FanzPay Command Center</title>
<meta name="description" content="Panel admin & super admin FanzPay: kelola pengguna, transaksi, produk, rekonsiliasi, dan audit. Akses khusus web.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0B1526">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>
@media(max-width:767px){
  .admin-shell .side{display:flex;position:fixed;left:0;top:0;bottom:0;transform:translateX(-105%);transition:transform .3s cubic-bezier(.22,.9,.28,1);width:270px}
  .admin-shell .side.on{transform:none;box-shadow:0 20px 70px rgba(0,0,0,.4)}
  .admin-shell .admin-backdrop{display:none}
  .admin-shell .side.on + .admin-backdrop{display:block;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:29}
}
</style>
</head>
<body>
<div class="shell admin-shell">
  <aside class="side dark" id="adminSide" aria-label="Navigasi admin">
    <a class="brand" href="<?= url('admin') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="44" height="44"><span>FanzPay <em><?= ($is_super ?? false) ? 'SUPER' : 'ADMIN' ?></em></span></a>
    <nav class="side-nav">
      <?php foreach ($menu as $m): ?>
        <a href="<?= $m['href'] ?>" class="<?= $nav === $m['k'] ? 'on' : '' ?>"><?= icon($m['icon'], 18) ?><span><?= e($m['label']) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <span class="who"><?= e($user['name'] ?? '') ?> · <?= e($user['role'] ?? '') ?></span>
      <a href="<?= url('logout') ?>"><?= icon('out', 18) ?><span>Keluar</span></a>
    </div>
  </aside>
  <div class="admin-backdrop" onclick="document.getElementById('adminSide').classList.remove('on')"></div>
  <div class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:10px">
        <button class="theme-toggle" onclick="document.getElementById('adminSide').classList.toggle('on')" aria-label="Menu" style="width:40px;height:40px;display:none" id="adminBurger"><?= icon('menu', 18) ?></button>
        <span class="top-title"><?= e($title ?? '') ?></span>
      </div>
      <div class="top-act">
        <button class="theme-toggle" data-theme-toggle aria-label="Mode" style="width:40px;height:40px"><span data-theme-icon><?= icon('moon', 18) ?></span></button>
        <span class="badge <?= ($is_super ?? false) ? 'b-info' : 'b-mut' ?>"><?= ($is_super ?? false) ? 'SUPER ADMIN' : 'ADMIN' ?></span>
        <a class="me" href="<?= url('logout') ?>" aria-label="Keluar"><span><?= e(mb_substr($user['name'] ?? 'A', 0, 1)) ?></span></a>
      </div>
    </header>
    <div class="content wide anim-fadeUp"><?= $content ?? '' ?></div>
  </div>
</div>
<script>
(function(){var b=document.getElementById('adminBurger');function t(){if(b)b.style.display=window.innerWidth<768?'inline-flex':'none'}window.addEventListener('resize',t);t();})();
</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
