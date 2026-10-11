<?php /** @var string|null $error */ /** @var string|null $ok */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>" class="on">Masuk</a><a href="<?= url('register') ?>">Daftar</a></div>
<div class="auth-hero">
  <span class="auth-medal"><?= icon('lock', 24) ?></span>
  <div>
    <h1>Selamat datang kembali</h1>
    <p class="sub">Satu pintu untuk pelanggan, admin, dan super admin.</p>
  </div>
</div>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($ok)): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<form method="post" action="<?= url('login') ?>">
  <?= \App\Core\CSRF::field() ?>
  <div class="field"><label for="email">Email terdaftar</label><input id="email" name="email" type="email" required autocomplete="email" placeholder="nama@email.com" value="<?= e(old_input('email')) ?>"></div>
  <div class="field"><label for="password">Kata sandi</label>
    <div class="pass-wrap"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi"><button type="button" class="pass-eye" data-peek="#password" aria-label="Tampilkan kata sandi"></button></div>
  </div>
  <button class="btn btn-primary btn-block btn-lg" type="submit">Masuk ke Akun <?= icon('arrowR', 17) ?></button>
</form>
<p class="auth-foot">Belum punya akun? <a href="<?= url('register') ?>"><strong>Daftar gratis.</strong></a></p>
<div class="auth-trust">
  <div><?= icon('lock', 17) ?><span>PIN 6 digit transaksi</span></div>
  <div><?= icon('checkCircle', 17) ?><span>Verifikasi email</span></div>
  <div><?= icon('receipt', 17) ?><span>Ledger teraudit</span></div>
</div>
