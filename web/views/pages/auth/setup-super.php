<?php /** @var string|null $error */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>">Masuk</a><a href="<?= url('setup-super') ?>" class="on">Setup Super</a></div>
<h1>Founding Circle 👑</h1>
<p class="sub">Ini <strong>satu-satunya kesempatan</strong> membuat Super Admin via web. Halaman ini <strong>terkunci permanen</strong> begitu satu super admin ada. Setelah itu, admin baru hanya bisa dibuat dari dalam panel.</p>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="alert ok" style="font-size:13.5px">🔒 URL rahasia pendiri: <b class="mono">/setup-super</b> · Rate-limit aktif · Semua percobaan dicatat di audit log.</div>
<form method="post" action="<?= url('setup-super') ?>">
  <?= \App\Core\CSRF::field() ?>
  <div class="field"><label for="name">Nama pendiri</label><input id="name" name="name" required maxlength="100" placeholder="Nama lengkapmu" value="<?= e(old_input('name')) ?>"></div>
  <div class="grid2">
    <div class="field"><label for="phone">Nomor HP</label><input id="phone" name="phone" inputmode="tel" placeholder="08…" required value="<?= e(old_input('phone')) ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required placeholder="founder@domain.com" value="<?= e(old_input('email')) ?>"></div>
  </div>
  <div class="field"><label for="password">Kata sandi super (min. 8)</label><input id="password" name="password" type="password" required minlength="8" placeholder="Yang kuat, jangan tanggal lahir"><div class="hint">Filosofi kami: kekuatan tanpa drama. Simpan di password manager.</div></div>
  <button class="btn btn-primary btn-block btn-lg" type="submit">Ciptakan Super Admin →</button>
</form>
<p style="margin-top:16px;font-size:13.5px;text-align:center" class="muted">Sudah ada super admin? Halaman ini akan mengarahkanmu ke <a href="<?= url('login') ?>">login</a> otomatis.</p>
