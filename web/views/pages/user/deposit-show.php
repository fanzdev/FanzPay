<?php /** @var array $d */ ?>
<div class="panel qr-box" data-dep-poll="<?= e($d['reference_id']) ?>" data-base="<?= url('') ?>">
  <div class="dash-head" style="text-align:left"><span class="dash-ic"><?= icon('qr', 22) ?></span><div><h3>Scan untuk membayar <?= rupiah((int)$d['total_amount']) ?></h3><p>Referensi <span class="mono"><?= e($d['reference_id']) ?></span> · <?= status_badge($d['status']) ?></p></div></div>
  <?php if (!empty($d['qr_url'])): ?>
    <img src="<?= e($d['qr_url']) ?>" alt="QRIS deposit" width="230" height="230">
  <?php else: ?>
    <div class="alert ok" style="text-align:left">Mode sandbox — tidak ada QR asli.
      <?php if (!empty($d['is_mock'])): ?>Deposit uji akan dikonfirmasi manual/admin atau via webhook <span class="mono">test=1</span>.<?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($d['payment_url'])): ?><p><a class="btn btn-ghost" href="<?= e($d['payment_url']) ?>">Buka halaman bayar</a></p><?php endif; ?>
  <p style="color:var(--mut);font-size:13px">Halaman ini memeriksa status otomatis. Jangan tutup sebelum lunas/kedaluwarsa.</p>
  <p><a class="btn btn-ghost" href="<?= url('deposit') ?>"><?= icon('arrowL', 16) ?> Buat deposit lain</a> <a class="btn btn-ghost" href="<?= url('riwayat') ?>"><?= icon('clock', 16) ?> Lihat riwayat</a></p>
</div>
