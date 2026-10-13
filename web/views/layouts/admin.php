<?php
/** Layout panel admin: sidebar navy + topbar glass + theme toggle. */
require_once fp_base_path('views/partials/icons.php');
/** @var string $title */ /** @var string $nav */ /** @var array $user */ /** @var bool $is_super */
$nav = $nav ?? 'dashboard';
$opKeys = ['tx', 'deposits', 'withdrawals', 'reconcile'];
$bizKeys = ['users', 'products', 'reports'];
$sysKeys = ['admins', 'notifications', 'audit', 'settings', 'apiconfig'];
$opOpen = in_array($nav, $opKeys, true);
$bizOpen = in_array($nav, $bizKeys, true);
$sysOpen = in_array($nav, $sysKeys, true);
$__isSuper = ($is_super ?? false);
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
    <a class="brand" href="<?= url('admin') ?>"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="44" height="44"><span>FanzPay <em><?= $__isSuper ? 'SUPER' : 'ADMIN' ?></em></span></a>
    <div class="side-role"><span class="side-role-ic"><?= icon($__isSuper ? 'shieldCheck' : 'shield', 18) ?></span><span><b><?= $__isSuper ? 'Super Admin' : 'Administrator' ?></b><small>Kontrol penuh operasional</small></span></div>
    <nav class="side-nav">
      <p class="side-cap">Utama</p>
      <a href="<?= url('admin') ?>" class="<?= $nav === 'dashboard' ? 'on' : '' ?>"><span class="side-ic"><?= icon('chart', 17) ?></span><span>Command Center</span></a>
      <p class="side-cap">Operasional</p>
      <details class="side-group"<?= $opOpen ? ' open' : '' ?>>
        <summary class="side-group-h<?= $opOpen ? ' on' : '' ?>"><span class="side-ic"><?= icon('swap', 17) ?></span><span>Transaksi Dana</span><span class="side-chev"><?= icon('chev', 14) ?></span></summary>
        <div class="side-sub">
          <a href="<?= url('admin/transaksi') ?>" class="<?= $nav === 'tx' ? 'on' : '' ?>"><span class="side-ic"><?= icon('clock', 15) ?></span><span>Transaksi</span></a>
          <a href="<?= url('admin/deposit') ?>" class="<?= $nav === 'deposits' ? 'on' : '' ?>"><span class="side-ic"><?= icon('plus', 15) ?></span><span>Deposit</span></a>
          <a href="<?= url('admin/withdraw') ?>" class="<?= $nav === 'withdrawals' ? 'on' : '' ?>"><span class="side-ic"><?= icon('card', 15) ?></span><span>Withdrawal</span></a>
          <a href="<?= url('admin/rekonsiliasi') ?>" class="<?= $nav === 'reconcile' ? 'on' : '' ?>"><span class="side-ic"><?= icon('refresh', 15) ?></span><span>Rekonsiliasi</span></a>
        </div>
      </details>
      <p class="side-cap">Bisnis</p>
      <details class="side-group"<?= $bizOpen ? ' open' : '' ?>>
        <summary class="side-group-h<?= $bizOpen ? ' on' : '' ?>"><span class="side-ic"><?= icon('users', 17) ?></span><span>Pengguna &amp; Produk</span><span class="side-chev"><?= icon('chev', 14) ?></span></summary>
        <div class="side-sub">
          <a href="<?= url('admin/pengguna') ?>" class="<?= $nav === 'users' ? 'on' : '' ?>"><span class="side-ic"><?= icon('user', 15) ?></span><span>Pengguna</span></a>
          <a href="<?= url('admin/produk') ?>" class="<?= $nav === 'products' ? 'on' : '' ?>"><span class="side-ic"><?= icon('grid', 15) ?></span><span>Produk &amp; Harga</span></a>
          <a href="<?= url('admin/laporan') ?>" class="<?= $nav === 'reports' ? 'on' : '' ?>"><span class="side-ic"><?= icon('inbox', 15) ?></span><span>Laporan</span></a>
        </div>
      </details>
      <p class="side-cap">Sistem</p>
      <details class="side-group"<?= $sysOpen ? ' open' : '' ?>>
        <summary class="side-group-h<?= $sysOpen ? ' on' : '' ?>"><span class="side-ic"><?= icon('gear', 17) ?></span><span>Tim &amp; Sistem</span><span class="side-chev"><?= icon('chev', 14) ?></span></summary>
        <div class="side-sub">
          <a href="<?= url('admin/admin') ?>" class="<?= $nav === 'admins' ? 'on' : '' ?>"><span class="side-ic"><?= icon('shield', 15) ?></span><span>Kelola Admin</span></a>
          <a href="<?= url('admin/notifikasi') ?>" class="<?= $nav === 'notifications' ? 'on' : '' ?>"><span class="side-ic"><?= icon('bell', 15) ?></span><span>Notifikasi</span></a>
          <a href="<?= url('admin/audit') ?>" class="<?= $nav === 'audit' ? 'on' : '' ?>"><span class="side-ic"><?= icon('search', 15) ?></span><span>Audit Log</span></a>
          <a href="<?= url('admin/pengaturan') ?>" class="<?= $nav === 'settings' ? 'on' : '' ?>"><span class="side-ic"><?= icon('gear', 15) ?></span><span>Pengaturan</span></a>
          <a href="<?= url('admin/api') ?>" class="<?= $nav === 'apiconfig' ? 'on' : '' ?>"><span class="side-ic"><?= icon('zap', 15) ?></span><span>API Config</span></a>
        </div>
      </details>
    </nav>
    <div class="side-foot">
      <a class="side-user" href="<?= url('admin/pengaturan') ?>"><span class="side-ava"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></span><span class="side-who"><b><?= e(mb_substr($user['name'] ?? 'Admin', 0, 22)) ?></b><small><?= e($user['role'] ?? '') ?></small></span></a>
      <a href="<?= url('logout') ?>"><span class="side-ic"><?= icon('out', 16) ?></span><span>Keluar</span></a>
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
