<?php /** @var array $user */ ?>
<div class="panel qr-box">
  <h3>QR saya</h3>
  <p style="color:var(--mut);font-size:14px">Orang lain scan untuk mengirimi Anda saldo — gratis.</p>
  <img src="https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=<?= urlencode(url('pay/' . $user['public_id'])) ?>" alt="QR FanzPay <?= e($user['name']) ?>" width="230" height="230" loading="lazy">
  <p class="secret">fanzpay://pay/u/<?= e($user['public_id']) ?></p>
  <p><a class="btn btn-ghost" href="<?= url('transfer?to=' . urlencode($user['public_id'])) ?>">Uji kirim ke diri sendiri (akan ditolak)</a></p>
</div>
