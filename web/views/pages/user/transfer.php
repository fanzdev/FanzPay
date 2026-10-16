<?php /** @var string|null $error */ /** @var string|null $ok */ /** @var string $lookup */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('swap', 22) ?></span><div><h3>Transfer gratis, tanpa biaya</h3><p>Ke sesama pengguna via nomor HP atau QR · masuk seketika.</p></div></div>
  <form method="post" action="<?= url('transfer') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="lookup">Nomor HP / ID penerima</label><input id="lookup" name="lookup" required value="<?= e($lookup) ?>" placeholder="08… atau FPXXXXXXXX"></div>
    <div class="field"><label for="amount">Nominal</label><input id="amount" name="amount" type="number" min="1000" inputmode="numeric" required placeholder="50000"></div>
    <div class="field"><label for="pin">PIN transaksi</label><input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="••••••"></div>
    <button class="btn btn-primary btn-block" type="submit">Kirim sekarang</button>
  </form>
  <ol class="guide">
    <li><span class="g-num">1</span><span><b>Cari penerima</b> via nomor HP, ID FP, atau pindai <a href="<?= url('qr') ?>"><strong>QR Code</strong></a>.</span></li>
    <li><span class="g-num">2</span><span><b>Isi nominal</b> lalu konfirmasi dengan PIN 6 digit.</span></li>
    <li><span class="g-num">3</span><span><b>Selesai</b> — dana masuk seketika &amp; tercatat di riwayat berdua.</span></li>
  </ol>
  <div class="info-note ok"><?= icon('checkCircle', 18) ?><span><strong>Biaya Rp0.</strong> Tidak ada potongan untuk pengirim maupun penerima.</span></div>
</div>
