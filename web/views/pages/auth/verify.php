<?php /** @var string $email */ /** @var string|null $dev_otp */ /** @var string|null $error */ ?>
<h1>Cek emailmu 📩</h1>
<p class="sub">Masukkan 6 digit kode yang dikirim ke <b><?= e($email) ?></b>. Berlaku 10 menit. Sabar, kode lagi OTW.</p>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($dev_otp): ?><div class="alert ok">Mode pengembangan — kode: <b class="mono"><?= e($dev_otp) ?></b></div><?php endif; ?>
<form method="post" action="<?= url('verify') ?>">
  <?= \App\Core\CSRF::field() ?>
  <input type="hidden" name="email" value="<?= e($email) ?>">
  <div class="field"><label for="otp">Kode verifikasi</label><input id="otp" name="otp" inputmode="numeric" maxlength="6" required placeholder="••••••" style="text-align:center;font-size:22px;font-weight:800;letter-spacing:.3em"></div>
  <button class="btn btn-primary btn-block btn-lg" type="submit">Verifikasi →</button>
</form>
<p class="muted" style="font-size:13px;text-align:center;margin-top:12px">Nggak dapat email? Cek spam/promosi dulu, bestie.</p>
