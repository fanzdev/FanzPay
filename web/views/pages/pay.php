<?php /** @var array $to */ ?>
<section class="hero"><div class="wrap" style="max-width:560px">
  <div class="card" style="text-align:center;padding:32px 24px">
    <img src="<?= asset('img/logo.webp') ?>" alt="FanzPay" width="52" height="52" style="border-radius:12px">
    <h1 style="font-size:16px;margin:14px 0 4px">Kirim ke <?= e($to['name']) ?></h1>
    <p class="sub">Pindai dari aplikasi atau lanjutkan setelah masuk.</p>
    <p class="secret">fanzpay://pay/u/<?= e($to['public_id']) ?></p>
    <div class="hero-cta" style="justify-content:center;margin-top:18px">
      <a class="btn btn-primary" href="<?= url('login') ?>">Masuk untuk kirim</a>
      <a class="btn btn-ghost" href="<?= url('register') ?>">Daftar</a>
    </div>
  </div>
</div></section>
