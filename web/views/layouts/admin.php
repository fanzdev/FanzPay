<?php
/**
 * Layout panel admin & super admin v3 — sidebar enterprise + topbar presisi.
 * Sidebar: brand · role · seksi navigasi datar (tanpa dropdown) · kartu pengguna + keluar.
 * Mobile: drawer off-canvas dengan backdrop. Semua warna/token di app.css (prefix as-).
 */
require_once fp_base_path('views/partials/icons.php');
/** @var string $title */ /** @var string $nav */ /** @var array $user */ /** @var bool $is_super */
/** @var string $panel */ /** @var array $my_perms */
$nav = $nav ?? 'dashboard';
$panel = $panel ?? 'admin';
$myPerms = $my_perms ?? [];
$__isSuper = ($is_super ?? false);
$can = fn(string $p): bool => $__isSuper || in_array($p, $myPerms, true);
// Seksi navigasi datar: [label, items[nav, label, icon, href, perm|null(super-only bila '__super')]]
$sections = [
  ['Transaksi Dana', [
    ['tx', 'Transaksi', 'clock', 'transaksi', 'transactions.view'],
    ['deposits', 'Deposit', 'plus', 'deposit', 'deposits.view'],
    ['withdrawals', 'Withdraw', 'card', 'withdraw', 'withdrawals.view'],
    ['reconcile', 'Rekonsiliasi', 'refresh', 'rekonsiliasi', 'reconcile.manage'],
  ]],
  ['Pengguna & Produk', [
    ['users', 'Pengguna', 'user', 'pengguna', 'users.view'],
    ['products', 'Produk & Harga', 'grid', 'produk', 'products.view'],
    ['reports', 'Laporan', 'inbox', 'laporan', 'reports.view'],
  ]],
  ['Tim & Sistem', [
    ['admins', 'Kelola Admin', 'shield', 'admin', '__super_admins'],
    ['notifications', 'Notifikasi', 'bell', 'notifikasi', 'notifications.manage'],
    ['audit', 'Audit Log', 'search', 'audit', 'audit.view'],
    ['settings', 'Pengaturan', 'gear', 'pengaturan', '__super_settings'],
    ['apiconfig', 'API Config', 'zap', 'api', '__super'],
    ['mcp', 'MCP Server', 'headset', 'mcp', '__super'],
  ]],
];
$visible = [];
foreach ($sections as [$slabel, $items]) {
    $its = [];
    foreach ($items as [$k, $label, $ic, $href, $perm]) {
        $show = $perm === '__super' ? $__isSuper
            : ($perm === '__super_admins' ? ($__isSuper || $can('admins.manage'))
            : ($perm === '__super_settings' ? ($__isSuper || $can('settings.manage'))
            : $can($perm)));
        if ($show) $its[] = [$k, $label, $ic, $href];
    }
    if ($its) $visible[] = [$slabel, $its];
}
$roleName = $__isSuper ? 'Super Admin' : 'Administrator';
$roleCode = $__isSuper ? 'super_admin' : 'admin';
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'Admin') ?> — FanzPay <?= $__isSuper ? 'Super Admin' : 'Admin' ?></title>
<meta name="description" content="Panel admin & super admin FanzPay: kelola pengguna, transaksi, produk, rekonsiliasi, dan audit. Akses khusus web.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0A1A33">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="shell admin-shell">
  <aside class="as-side" id="adminSide" aria-label="Navigasi <?= $__isSuper ? 'super admin' : 'admin' ?>">
    <div class="as-brand">
      <a class="as-brand-link" href="<?= url($panel) ?>">
        <span class="as-logo"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="38" height="38"></span>
        <span class="as-brand-tx"><b>FanzPay</b><small>Dompet Digital &amp; PPOB</small></span>
      </a>
      <span class="as-rolepill"><?= $__isSuper ? 'SUPER' : 'ADMIN' ?></span>
    </div>

    <div class="as-role">
      <span class="as-role-ic"><?= icon($__isSuper ? 'shieldCheck' : 'shield', 18) ?></span>
      <span class="as-role-tx">
        <b><?= e($roleName) ?></b>
        <small><?= $__isSuper ? 'Kontrol penuh sistem' : 'Akses sesuai permission' ?></small>
      </span>
    </div>

    <nav class="as-nav" aria-label="Menu panel">
      <?php $ii = 0; foreach ($visible as [$slabel, $its]): ?>
      <div class="as-sec">
        <span class="as-sec-h"><?= e($slabel) ?></span>
        <?php foreach ($its as [$k, $label, $ic, $href]): $on = $nav === $k; ?>
        <a class="as-item<?= $on ? ' on' : '' ?>" style="--i:<?= $ii++ ?>" href="<?= url($panel . '/' . $href) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
          <span class="as-ic"><?= icon($ic, 17) ?></span><span class="as-lb"><?= e($label) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </nav>

    <div class="as-foot">
      <div class="as-user" title="Akun panel">
        <span class="as-ava"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></span>
        <span class="as-who"><b><?= e(mb_substr($user['name'] ?? 'Admin', 0, 22)) ?></b><small><?= e($roleCode) ?></small></span>
      </div>
      <a class="as-out" href="<?= url('logout') ?>" title="Keluar" aria-label="Keluar dari panel"><?= icon('out', 17) ?></a>
    </div>
  </aside>
  <div class="as-backdrop" id="adminBackdrop" aria-hidden="true"></div>

  <div class="main">
    <header class="as-topbar">
      <div class="as-tb-left">
        <button class="as-burger" id="adminBurger" aria-label="Buka menu" aria-expanded="false"><?= icon('menu', 19) ?></button>
        <div class="as-tb-tx">
          <small><?= $__isSuper ? 'Super Admin' : 'Admin' ?></small>
          <span class="as-tb-title"><?= e($title ?? '') ?></span>
        </div>
      </div>
      <div class="as-tb-right">
        <a class="as-ico" href="<?= url('') ?>" title="Lihat situs publik" aria-label="Lihat situs publik"><?= icon('globe', 18) ?></a>
        <button type="button" class="as-ico" data-theme-toggle aria-label="Ganti mode gelap atau terang"><span data-theme-icon><?= icon('moon', 18) ?></span></button>
        <span class="badge <?= $__isSuper ? 'b-info' : 'b-mut' ?> as-tb-badge"><?= $__isSuper ? 'SUPER ADMIN' : 'ADMIN' ?></span>
        <a class="as-out-btn" href="<?= url('logout') ?>"><?= icon('out', 16) ?><span>Keluar</span></a>
      </div>
    </header>
    <div class="content wide as-content anim-fadeUp"><?= $content ?? '' ?></div>
  </div>
</div>
<script>
(function(){
  var side=document.getElementById('adminSide');
  if(!side)return;
  var bd=document.getElementById('adminBackdrop'),bg=document.getElementById('adminBurger');
  var mq=window.matchMedia('(max-width:767px)');
  function setMenu(open){
    side.classList.toggle('on',!!open);
    if(bd)bd.classList.toggle('on',!!open);
    if(bg)bg.setAttribute('aria-expanded',open?'true':'false');
    document.body.style.overflow=(open&&mq.matches)?'hidden':'';
  }
  if(bg)bg.addEventListener('click',function(){setMenu(!side.classList.contains('on'));});
  if(bd)bd.addEventListener('click',function(){setMenu(false);});
  window.addEventListener('resize',function(){if(!mq.matches)setMenu(false);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')setMenu(false);});
})();
</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
