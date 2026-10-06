<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $t */ /** @var array $items */ /** @var array $ledger */
?>
<div class="panel">
  <h3><?= e(kind_label($t['kind'])) ?> · <?= status_badge($t['status']) ?></h3>
  <p class="secret mono"><?= e($t['reference_id']) ?></p>
  <table class="tbl" style="min-width:0">
    <tr><th>Nominal</th><td class="mono"><?= rupiah((int)$t['amount']) ?></td></tr>
    <tr><th>Biaya</th><td class="mono"><?= rupiah((int)$t['fee']) ?></td></tr>
    <tr><th>Total</th><td class="mono"><b><?= rupiah((int)$t['total']) ?></b></td></tr>
    <tr><th>Keterangan</th><td><?= e($t['note']) ?></td></tr>
    <?php if (!empty($t['provider_ref'])): ?><tr><th>Referensi provider</th><td class="mono"><?= e($t['provider_ref']) ?></td></tr><?php endif; ?>
    <tr><th>Waktu</th><td><?= e($t['created_at']) ?></td></tr>
  </table>
</div>
<?php if (!empty($ledger)): ?>
<div class="panel"><h3>Jejak ledger</h3>
  <?php foreach ($ledger as $l): ?>
    <div class="tx-row"><span class="tx-main"><b><?= e($l['type']) ?> · <?= e($l['direction']) ?></b><small class="mono"><?= e($l['transaction_id']) ?></small></span><span class="tx-amt"><?= ($l['direction'] === 'credit' ? '+' : '−') . rupiah((int)$l['amount']) ?></span></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
