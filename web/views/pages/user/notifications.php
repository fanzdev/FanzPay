<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */
?>
<div class="panel"><h3>Notifikasi</h3>
<?php if (empty($rows)): ?><div class="empty"><?= icon('bell', 28) ?><p>Tidak ada notifikasi.</p></div><?php endif; ?>
<?php foreach ($rows as $n): ?>
  <div class="tx-row"><span class="tx-main"><b><?= e($n['title']) ?></b><small><?= e($n['body']) ?> · <?= e(date('d M H:i', strtotime($n['created_at']))) ?></small></span></div>
<?php endforeach; ?>
</div>
