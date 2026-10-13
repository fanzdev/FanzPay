<?php /** @var string|null $error */ /** @var int $min */ /** @var int $max */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('qr', 22) ?></span><div><h3>Deposit via QRIS</h3><p>Minimal <?= rupiah($min) ?> · maksimal <?= rupiah($max) ?> · terverifikasi otomatis.</p></div></div>
  <form method="post" action="<?= url('deposit') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="amount">Nominal deposit</label><input id="amount" name="amount" type="number" min="<?= $min ?>" max="<?= $max ?>" required placeholder="50000"></div>
    <div class="grid2">
      <?php foreach ([50000, 100000, 250000, 500000] as $q): ?>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('amount').value=<?= $q ?>"><?= rupiah($q) ?></button>
      <?php endforeach; ?>
    </div>
    <div style="height:12px"></div>
    <button class="btn btn-primary btn-block" type="submit">Buat QRIS</button>
  </form>
  <ol class="guide">
    <li><span class="g-num">1</span><span><b>Pilih nominal</b> atau ketuk nominal cepat di atas.</span></li>
    <li><span class="g-num">2</span><span><b>Pindai QRIS</b> dari e-wallet / m-banking apa pun.</span></li>
    <li><span class="g-num">3</span><span><b>Saldo masuk otomatis</b> — pantau di halaman bayar &amp; riwayat.</span></li>
  </ol>
  <div class="info-note"><?= icon('zap', 18) ?><span>QR dibuat di server &amp; status diverifikasi otomatis. Jangan tutup halaman bayar sebelum lunas.</span></div>
</div>
