<?php /** @var string|null $error */ ?>
<div class="auth-switch"><a href="<?= url('login') ?>">Masuk</a><a href="<?= url('register') ?>" class="on">Daftar</a></div>
<div class="auth-hero">
  <span class="auth-medal"><?= icon('user', 24) ?></span>
  <div>
    <h1>Buat akun FanzPay</h1>
    <p class="sub">Gratis, tanpa kartu kredit. Tiga langkah singkat.</p>
  </div>
</div>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= url('register') ?>" data-wizard data-idem>
  <?= \App\Core\CSRF::field() ?>
  <input type="hidden" name="_idem" value="">
  <div class="wiz-bar" aria-hidden="true">
    <div class="wiz-step" data-wstep><span class="dot">1</span>Data Diri</div>
    <div class="wiz-step" data-wstep><span class="dot">2</span>Keamanan</div>
    <div class="wiz-step" data-wstep><span class="dot">3</span>Konfirmasi</div>
  </div>

  <div class="wiz-panel" data-wpanel>
    <div class="field"><label for="w-name">Nama lengkap</label><input id="w-name" name="name" data-req required maxlength="100" autocomplete="name" placeholder="Nama lengkap sesuai identitas" value="<?= e(old_input('name')) ?>"><small class="wiz-err" data-werr></small></div>
    <div class="field"><label for="w-phone">Nomor HP aktif</label><div class="phone-field"><span class="phone-prefix" aria-hidden="true"><span class="flag-id"><i></i><i></i></span><b>+62</b></span><input id="w-phone" name="phone" data-req data-phone inputmode="tel" autocomplete="tel" placeholder="812 xxxx xxxx" required value="<?= e(old_input('phone')) ?>"></div><small class="wiz-err" data-werr></small></div>
    <div class="wiz-nav"><button type="button" class="btn btn-primary btn-lg" data-wnext>Lanjut <?= icon('arrowR', 17) ?></button></div>
  </div>

  <div class="wiz-panel" data-wpanel>
    <div class="field"><label for="w-email">Email aktif</label><input id="w-email" name="email" data-req type="email" autocomplete="email" placeholder="nama@email.com" required value="<?= e(old_input('email')) ?>"><small class="wiz-err" data-werr></small><div class="hint">Kode verifikasi (OTP) dikirim ke email ini.</div></div>
    <div class="grid2">
      <div class="field"><label for="w-pass">Kata sandi</label>
        <div class="pass-wrap"><input id="w-pass" name="password" data-req data-min="8" data-strength="#w-meter" type="password" required minlength="8" autocomplete="new-password" placeholder="Min. 8 karakter"><button type="button" class="pass-eye" data-peek="#w-pass" aria-label="Tampilkan kata sandi" tabindex="-1"></button></div>
        <div class="strength" id="w-meter"><i></i><i></i><i></i><i></i><small data-slabel>Isi kata sandi</small></div>
        <small class="wiz-err" data-werr></small>
      </div>
      <div class="field"><label for="w-pass2">Ulangi kata sandi</label>
        <div class="pass-wrap"><input id="w-pass2" name="password_confirm" data-req data-match="password" type="password" required autocomplete="new-password" placeholder="Sama persis"><button type="button" class="pass-eye" data-peek="#w-pass2" aria-label="Tampilkan kata sandi" tabindex="-1"></button></div>
        <small class="wiz-err" data-werr></small>
      </div>
    </div>
    <div class="hint">PIN 6 digit untuk transaksi dibuat terpisah setelah verifikasi email.</div>
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
      <button type="button" class="review-edit" data-wgoto="0">Ubah data diri</button>
      <span class="muted">·</span>
      <button type="button" class="review-edit" data-wgoto="1">Ubah keamanan</button>
    </div>
    <div class="wiz-nav"><button type="button" class="btn btn-ghost" data-wback><?= icon('arrowL', 16) ?> Kembali</button><button type="submit" class="btn btn-primary btn-lg">Daftar dan Kirim Kode <?= icon('arrowR', 17) ?></button></div>
  </div>
</form>
<p class="auth-foot">Sudah punya akun? <a href="<?= url('login') ?>"><strong>Masuk di sini.</strong></a></p>
<div class="auth-trust">
  <div><?= icon('checkCircle', 17) ?><span>Verifikasi OTP email</span></div>
  <div><?= icon('lock', 17) ?><span>PIN 6 digit transaksi</span></div>
  <div><?= icon('wallet', 17) ?><span>Deposit QRIS</span></div>
</div>
