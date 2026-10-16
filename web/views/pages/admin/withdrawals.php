<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var bool $can_manage */
?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Referensi</th><th>User</th><th>Tujuan</th><th>Nominal</th><th>Status</th><?php if ($can_manage): ?><th>Aksi</th><?php endif; ?></tr>
  <?php if (empty($rows)): ?><tr><td colspan="<?= $can_manage ? 6 : 5 ?>" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
  <?php foreach ($rows as $w): ?>
    <tr><td class="mono"><?= e($w['reference_id']) ?></td><td><?= e($w['name']) ?></td><td><?= e($w['dest']) ?></td><td class="mono"><?= rupiah((int)$w['amount']) ?></td><td><?= status_badge($w['status']) ?></td>
    <?php if ($can_manage): ?><td><?php if ($w['status'] === 'pending'): ?>
      <form method="post" action="<?= url($panel . '/withdraw/' . $w['reference_id'] . '/reject') ?>" data-confirm="Tolak & kembalikan saldo?"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Tolak + refund</button></form>
    <?php else: ?>—<?php endif; ?></td><?php endif; ?></tr>
  <?php endforeach; ?>
</table></div></div>
