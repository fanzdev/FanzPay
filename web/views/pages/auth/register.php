<?php /** @var string|null $error */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>">Masuk</a><a href="<?= url('register') ?>" class="on">Daftar</a></div>
<h1>Mulai cerita uangmu 🚀</h1>
<p class="sub">Gratis. Tanpa kartu kredit. Kode verifikasi meluncur ke emailmu — pelan tapi pasti.</p>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= url('register') ?>">
  <?= \App\Core\CSRF::field() ?>
  <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" required maxlength="100" placeholder="Nama panggilanmu" value="<?= e(old_input('name')) ?>"></div>
  <div class="grid2">
    <div class="field"><label for="phone">Nomor HP</label><input id="phone" name="phone" inputmode="tel" placeholder="08…" required value="<?= e(old_input('phone')) ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required placeholder="kamu@email.com" value="<?= e(old_input('email')) ?>"></div>
  </div>
  <div class="field"><label for="password">Kata sandi</label><input id="password" name="password" type="password" required minlength="8" placeholder="Minimal 8 karakter, yang kuat ya"><div class="hint">Dipakai untuk masuk. Nanti kamu bikin <strong>PIN 6 digit</strong> terpisah untuk transaksi. Beda, biar aman.</div></div>
  <button class="btn btn-primary btn-block btn-lg" type="submit">Daftar &amp; kirim kode →</button>
</form>
<p style="margin-top:16px;font-size:14px;text-align:center">Sudah punya akun? <a href="<?= url('login') ?>"><strong>Masuk di sini</strong></a> (satu pintu untuk semua peran).</p>
