<?php /** @var string|null $error */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>">Masuk</a><a href="<?= url('register') ?>">Daftar</a></div>
<div class="auth-hero">
  <span class="auth-medal dark"><?= icon('shield', 24) ?></span>
  <div>
    <h1>Registrasi Super Admin</h1>
    <p class="sub">Akun pemilik sistem. Tiga langkah singkat.</p>
  </div>
</div>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= url('setup-super') ?>" data-wizard>
  <?= \App\Core\CSRF::field() ?>
  <div class="wiz-bar" aria-hidden="true">
    <div class="wiz-step" data-wstep><span class="dot">1</span>Data Pendiri</div>
    <div class="wiz-step" data-wstep><span class="dot">2</span>Keamanan</div>
    <div class="wiz-step" data-wstep><span class="dot">3</span>Konfirmasi</div>
  </div>

  <div class="wiz-panel" data-wpanel>
    <div class="field"><label for="s-name">Nama lengkap pendiri</label><input id="s-name" name="name" data-req required maxlength="100" autocomplete="name" placeholder="Nama lengkap sesuai identitas" value="<?= e(old_input('name')) ?>"><small class="wiz-err" data-werr></small></div>
    <div class="field"><label for="s-phone">Nomor HP aktif</label><div class="phone-field"><span class="phone-prefix" aria-hidden="true"><span class="flag-id"><i></i><i></i></span><b>+62</b></span><input id="s-phone" name="phone" data-req data-phone inputmode="tel" autocomplete="tel" placeholder="812 xxxx xxxx" required value="<?= e(old_input('phone')) ?>"></div><small class="wiz-err" data-werr></small></div>
    <div class="wiz-nav"><button type="button" class="btn btn-primary btn-lg" data-wnext>Lanjut <?= icon('arrowR', 17) ?></button></div>
  </div>

  <div class="wiz-panel" data-wpanel>
    <div class="field"><label for="s-email">Email utama</label><input id="s-email" name="email" data-req type="email" autocomplete="email" placeholder="nama@domain.com" required value="<?= e(old_input('email')) ?>"><small class="wiz-err" data-werr></small><div class="hint">Email permanen untuk akses dan pemulihan akun.</div></div>
    <div class="grid2">
      <div class="field"><label for="s-pass">Kata sandi super</label>
        <div class="pass-wrap"><input id="s-pass" name="password" data-req data-min="8" data-strength="#s-meter" type="password" required minlength="8" autocomplete="new-password" placeholder="Min. 8 karakter"><button type="button" class="pass-eye" data-peek="#s-pass" aria-label="Tampilkan kata sandi" tabindex="-1"></button></div>
        <div class="strength" id="s-meter"><i></i><i></i><i></i><i></i><small data-slabel>Isi kata sandi</small></div>
        <small class="wiz-err" data-werr></small>
      </div>
      <div class="field"><label for="s-pass2">Ulangi kata sandi</label>
        <div class="pass-wrap"><input id="s-pass2" name="password_confirm" data-req data-match="password" type="password" required autocomplete="new-password" placeholder="Sama persis"><button type="button" class="pass-eye" data-peek="#s-pass2" aria-label="Tampilkan kata sandi" tabindex="-1"></button></div>
        <small class="wiz-err" data-werr></small>
      </div>
    </div>
    <div class="hint">Gunakan kombinasi huruf besar-kecil, angka, dan simbol. Simpan di password manager.</div>
    <div class="wiz-nav"><button type="button" class="btn btn-ghost" data-wback><?= icon('arrowL', 16) ?> Kembali</button><button type="button" class="btn btn-primary btn-lg" data-wnext>Lanjut <?= icon('arrowR', 17) ?></button></div>
  </div>

  <div class="wiz-panel" data-wpanel>
    <div class="review-box">
      <div class="review-row"><small>Nama lengkap</small><b data-review="name">—</b></div>
      <div class="review-row"><small>Nomor HP</small><b data-review="phone">—</b></div>
      <div class="review-row"><small>Email</small><b data-review="email">—</b></div>
      <div class="review-row"><small>Kata sandi</small><b data-review="password">—</b></div>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:4px">
      <button type="button" class="review-edit" data-wgoto="0">Ubah data</button>
      <span class="muted">·</span>
      <button type="button" class="review-edit" data-wgoto="1">Ubah keamanan</button>
    </div>
    <div class="wiz-nav"><button type="button" class="btn btn-ghost" data-wback><?= icon('arrowL', 16) ?> Kembali</button><button type="submit" class="btn btn-primary btn-lg">Buat Super Admin <?= icon('arrowR', 17) ?></button></div>
  </div>
</form>
<p class="auth-foot">Sudah punya akun? <a href="<?= url('login') ?>"><strong>Masuk di sini.</strong></a></p>
<div class="auth-trust">
  <div><?= icon('eye', 17) ?><span>Rate-limit aktif</span></div>
  <div><?= icon('file', 17) ?><span>Audit log</span></div>
  <div><?= icon('lock', 17) ?><span>Otomatis terkunci</span></div>
</div>
