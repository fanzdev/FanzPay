<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $data */
?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="stat-grid" style="grid-template-columns:1fr 1fr">
  <div class="stat"><small>Pending &gt; 30 menit</small><b class="mono"><?= count($data['old_pending']) ?></b></div>
  <div class="stat"><small>Selisih saldo vs ledger</small><b class="mono"><?= count($data['balance_mismatch']) ?></b></div>
</div>
<div class="panel"><h3>Pending lama — butuh cek ke provider</h3>
  <?php if (empty($data['old_pending'])): ?><p style="color:var(--mut)">Bersih. Tidak ada transaksi menggantung.</p><?php endif; ?>
  <?php foreach ($data['old_pending'] as $t): ?>
    <div class="tx-row"><span class="tx-main"><b class="mono"><?= e($t['reference_id']) ?></b><small><?= e($t['kind']) ?> · <?= rupiah((int)$t['total']) ?> · <?= e($t['created_at']) ?></small></span>
    <?php if ($t['kind'] === 'ppob'): ?><form method="post" action="<?= url('admin/rekonsiliasi/' . $t['reference_id'] . '/refund') ?>" data-confirm="Refund transaksi ini?"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Refund</button></form><?php endif; ?></div>
  <?php endforeach; ?>
</div>
<div class="panel"><h3>Webhook belum selesai</h3>
  <?php if (empty($data['webhooks_open'])): ?><p style="color:var(--mut)">Semua webhook terproses.</p><?php endif; ?>
  <?php foreach ($data['webhooks_open'] as $w): ?><div class="tx-row"><span class="tx-main"><b><?= e($w['provider']) ?> · <?= e($w['event']) ?></b></span><span class="tx-amt"><?= (int)$w['c'] ?></span></div><?php endforeach; ?>
</div>
<div class="panel"><h3>Selisih saldo</h3>
  <?php if (empty($data['balance_mismatch'])): ?><p style="color:var(--mut)">Nihil — saldo cocok dengan ledger.</p><?php endif; ?>
  <?php foreach ($data['balance_mismatch'] as $m): ?><div class="tx-row"><span class="tx-main"><b>User #<?= (int)$m['user_id'] ?></b><small>tersimpan <?= rupiah((int)$m['stored']) ?> vs ledger <?= rupiah((int)$m['ledger']) ?></small></span></div><?php endforeach; ?>
</div>
