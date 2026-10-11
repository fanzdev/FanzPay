<?php /** @var string $title */
require_once fp_base_path('views/partials/icons.php'); ?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'FanzPay') ?></title>
<meta name="description" content="Masuk atau buat akun FanzPay. Satu pintu untuk pelanggan, admin, dan super admin. Pelanggan bisa lewat web & Android; admin & super admin lewat web.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0B1526">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-bg" aria-hidden="true"><i></i><i></i><i></i></div>
<div class="auth-split">
  <aside class="auth-show" aria-label="Tentang FanzPay">
    <div class="auth-show-inner">
      <a class="brand auth-show-brand" href="<?= url('') ?>">
        <img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="38" height="38">
        <span>FanzPay</span>
      </a>
      <h2>Satu saldo untuk semua kebutuhan digitalmu.</h2>
      <p>Dompet digital dan PPOB: deposit QRIS real-time, transfer gratis, tarik dana, dan 8 layanan dalam satu dashboard.</p>
      <ul class="auth-feats">
        <li><?= icon('qr', 19) ?><span><strong>Deposit QRIS real-time</strong><small>Status terverifikasi otomatis via webhook.</small></span></li>
        <li><?= icon('swap', 19) ?><span><strong>Transfer antar pengguna Rp0</strong><small>Via nomor HP atau pindai QR Code.</small></span></li>
        <li><?= icon('shieldCheck', 19) ?><span><strong>Keamanan berlapis</strong><small>PIN 6 digit, verifikasi email, ledger teraudit.</small></span></li>
      </ul>
    </div>
  </aside>
  <main class="auth-form">
    <div class="auth-form-inner">
      <div class="auth-card"><?= $content ?? '' ?></div>
      <p class="auth-note">Dilindungi PIN 6 digit · Verifikasi email · Ledger yang bisa diaudit.<br>Pelanggan bisa masuk via web &amp; Android · Admin &amp; Super Admin via web.</p>
    </div>
  </main>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
