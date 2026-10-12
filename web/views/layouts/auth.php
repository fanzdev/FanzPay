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
<meta name="theme-color" content="#1B4DFF">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-split">
  <aside class="auth-show" aria-label="Tentang FanzPay">
    <div class="auth-deco" aria-hidden="true"><i></i><i></i></div>
    <div class="auth-show-inner">
      <a class="brand auth-show-brand" href="<?= url('') ?>">
        <span class="auth-logo"><img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="44" height="44"></span>
        <span class="auth-brand-tx">FanzPay<em>Dompet Digital &amp; PPOB</em></span>
      </a>
      <h2>Satu saldo untuk semua kebutuhan digital.</h2>
      <p class="auth-lead">Dompet digital &amp; PPOB Indonesia: deposit QRIS real-time, transfer gratis, tarik dana, dan 8 layanan dalam satu dashboard.</p>
      <ul class="auth-feats">
        <li><?= icon('checkCircle', 18) ?><span><strong>Deposit QRIS real-time</strong><small>Saldo masuk otomatis setelah terverifikasi.</small></span></li>
        <li><?= icon('checkCircle', 18) ?><span><strong>Transfer gratis Rp0</strong><small>Via nomor HP atau pindai QR Code.</small></span></li>
        <li><?= icon('checkCircle', 18) ?><span><strong>Aman &amp; tercatat</strong><small>PIN 6 digit, OTP email, ledger teraudit.</small></span></li>
      </ul>
      <p class="auth-small">© <?= date('Y') ?> FanzPay</p>
    </div>
  </aside>
  <main class="auth-form">
    <div class="auth-form-inner">
      <div class="auth-card"><?= $content ?? '' ?></div>
      <p class="auth-note">PIN 6 digit · Verifikasi email · Ledger teraudit.</p>
    </div>
  </main>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
