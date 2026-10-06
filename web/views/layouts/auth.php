<?php /** @var string $title */ ?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title ?? 'FanzPay') ?></title>
<meta name="description" content="Masuk atau buat akun FanzPay. Satu pintu untuk pelanggan, admin, dan super admin. Pelanggan bisa lewat web & Android; admin & super admin lewat web.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#F4F6FB">
<link rel="icon" type="image/webp" href="<?= asset('img/logo.webp') ?>">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('fanzpay-theme')||'light')}catch(e){document.documentElement.setAttribute('data-theme','light')}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-bg" aria-hidden="true"><i></i><i></i><i></i></div>
<main class="auth-wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
    <a class="brand auth-brand" href="<?= url('') ?>" style="margin:0">
      <img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="40" height="40">
      <span>FanzPay</span>
    </a>
    <button class="theme-toggle" data-theme-toggle aria-label="Ganti mode"><span data-theme-icon>🌙</span></button>
  </div>
  <div class="auth-card"><?= $content ?? '' ?></div>
  <p class="auth-note">🛡️ Dilindungi PIN 6 digit · Verifikasi email · Ledger yang bisa diaudit.<br>Pelanggan bisa masuk via web &amp; Android · Admin &amp; Super Admin via web.</p>
</main>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
