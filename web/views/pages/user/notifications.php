<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */
?>
<div class="panel"><div class="dash-head"><span class="dash-ic"><?= icon('bell', 22) ?></span><div><h3>Notifikasi</h3><p>Status transaksi &amp; info akun terbaru.</p></div></div>
<?php if (empty($rows)): ?><div class="empty"><?= icon('bell', 28) ?><p>Tidak ada notifikasi.</p></div><?php endif; ?>
<?php foreach ($rows as $n): ?>
  <div class="tx-row"><span class="tx-ic"><?= icon('bell', 18) ?></span><span class="tx-main"><b><?= e($n['title']) ?></b><small><?= e($n['body']) ?> · <?= e(date('d M H:i', strtotime($n['created_at']))) ?></small></span></div>
<?php endforeach; ?>
<div class="info-note"><?= icon('checkCircle', 18) ?><span>Notifikasi terkirim otomatis setiap ada perubahan status <strong>berhasil, pending, gagal, atau refund</strong>.</span></div>
</div>
