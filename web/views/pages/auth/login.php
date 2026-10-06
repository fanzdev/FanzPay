<?php /** @var string|null $error */ /** @var string|null $ok */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>" class="on">Masuk</a><a href="<?= url('register') ?>">Daftar</a></div>
<h1>Welcome back, pejuang 👋</h1>
<p class="sub"><strong>Satu pintu untuk semua peran.</strong> Pelanggan, admin &amp; super admin masuk di sini — sistem otomatis mengarahkanmu ke tempat yang tepat.</p>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($ok)): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<form method="post" action="<?= url('login') ?>">
  <?= \App\Core\CSRF::field() ?>
  <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="email" placeholder="kamu@email.com" value="<?= e(old_input('email')) ?>"></div>
  <div class="field"><label for="password">Kata sandi</label>
    <div style="display:flex;gap:8px"><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" style="flex:1"><button type="button" class="btn btn-ghost btn-sm" data-peek="#password">Lihat</button></div>
  </div>
  <button class="btn btn-primary btn-block btn-lg" type="submit">Masuk →</button>
</form>
<p style="margin-top:16px;font-size:14px;text-align:center">Belum punya akun? <a href="<?= url('register') ?>"><strong>Daftar gratis, 2 menit.</strong></a></p>
<p class="muted" style="font-size:12.5px;text-align:center;margin:10px 0 0">🤖 Pakai Android? Aplikasi khusus <strong>pelanggan</strong>. Admin &amp; Super Admin wajib lewat web ini.<br>👑 Belum ada super admin? <a href="<?= url('setup-super') ?>">Buka setup-super</a> (terkunci otomatis setelah 1 akun ada).</p>
