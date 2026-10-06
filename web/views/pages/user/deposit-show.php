<?php /** @var array $d */ ?>
<div class="panel qr-box" data-dep-poll="<?= e($d['reference_id']) ?>" data-base="<?= url('') ?>">
  <h3>Scan untuk membayar <?= rupiah((int)$d['total_amount']) ?></h3>
  <p style="color:var(--mut);font-size:14px">Referensi <span class="mono"><?= e($d['reference_id']) ?></span> · <?= status_badge($d['status']) ?></p>
  <?php if (!empty($d['qr_url'])): ?>
    <img src="<?= e($d['qr_url']) ?>" alt="QRIS deposit" width="230" height="230">
  <?php else: ?>
    <div class="alert ok" style="text-align:left">Mode sandbox — tidak ada QR asli.
      <?php if (!empty($d['is_mock'])): ?>Deposit uji akan dikonfirmasi manual/admin atau via webhook <span class="mono">test=1</span>.<?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($d['payment_url'])): ?><p><a class="btn btn-ghost" href="<?= e($d['payment_url']) ?>">Buka halaman bayar</a></p><?php endif; ?>
  <p style="color:var(--mut);font-size:13px">Halaman ini memeriksa status otomatis. Jangan tutup sebelum lunas/kedaluwarsa.</p>
</div>
