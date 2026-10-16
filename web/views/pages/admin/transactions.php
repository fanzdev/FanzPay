<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var string $status */
?>
<div class="chips">
  <?php foreach (['' => 'Semua', 'pending' => 'Pending', 'processing' => 'Processing', 'success' => 'Sukses', 'failed' => 'Gagal', 'refunded' => 'Refund'] as $k => $l): ?>
    <a class="chip <?= $status === $k ? 'on' : '' ?>" href="<?= url($panel . '/transaksi' . ($k !== '' ? '?status=' . $k : '')) ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Referensi</th><th>User</th><th>Jenis</th><th>Total</th><th>Status</th><th>Waktu</th></tr>
  <?php if (empty($rows)): ?><tr><td colspan="6" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
  <?php foreach ($rows as $t): ?>
    <tr><td class="mono"><?= e($t['reference_id']) ?></td><td><?= e($t['name']) ?></td><td><?= e(kind_label($t['kind'])) ?></td><td class="mono"><?= rupiah((int)$t['total']) ?></td><td><?= status_badge($t['status']) ?></td><td><small><?= e($t['created_at']) ?></small></td></tr>
  <?php endforeach; ?>
</table></div></div>
