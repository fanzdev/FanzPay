<?php /** @var array $user */ ?>
<div class="panel qr-box">
  <div class="dash-head" style="text-align:left"><span class="dash-ic"><?= icon('qr', 22) ?></span><div><h3>QR saya</h3><p>Orang lain scan untuk mengirimi Anda saldo — gratis.</p></div></div>
  <img src="https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=<?= urlencode(url('pay/' . $user['public_id'])) ?>" alt="QR FanzPay <?= e($user['name']) ?>" width="230" height="230" loading="lazy">
  <p class="secret">fanzpay://pay/u/<?= e($user['public_id']) ?></p>
  <p><a class="btn btn-ghost" href="<?= url('transfer?to=' . urlencode($user['public_id'])) ?>">Uji kirim ke diri sendiri (akan ditolak)</a></p>
  <ol class="guide" style="text-align:left">
    <li><span class="g-num">1</span><span><b>Tunjukkan QR</b> ke pengirim — bisa di-screenshot.</span></li>
    <li><span class="g-num">2</span><span><b>Pengirim transfer</b> via menu Transfer atau halaman pay.</span></li>
    <li><span class="g-num">3</span><span><b>Saldo masuk seketika</b> tanpa potongan apa pun.</span></li>
  </ol>
</div>
