<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */
?>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Referensi</th><th>User</th><th>Nominal</th><th>Total bayar</th><th>Status</th><th>Mock</th></tr>
  <?php foreach ($rows as $d): ?>
    <tr><td class="mono"><?= e($d['reference_id']) ?><br><small class="mono" style="color:var(--mut)"><?= e((string)($d['provider_txn_id'] ?? '')) ?></small></td><td><?= e($d['name']) ?></td><td class="mono"><?= rupiah((int)$d['amount']) ?></td><td class="mono"><?= rupiah((int)$d['total_amount']) ?></td><td><?= status_badge($d['status']) ?></td><td><?= $d['is_mock'] ? 'ya' : '—' ?></td></tr>
  <?php endforeach; ?>
</table></div></div>
