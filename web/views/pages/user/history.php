<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var string $kind */
?>
<div class="chips">
  <?php foreach (['' => 'Semua', 'deposit' => 'Deposit', 'transfer' => 'Transfer', 'ppob' => 'PPOB', 'withdraw' => 'Withdraw'] as $k => $l): ?>
    <a class="chip <?= $kind === $k ? 'on' : '' ?>" href="<?= url('riwayat' . ($k !== '' ? '?kind=' . $k : '')) ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>
<div class="panel">
  <?php if (empty($rows)): ?><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada riwayat.</p></div><?php endif; ?>
  <?php foreach ($rows as $t): ?>
    <a class="tx-row" href="<?= url('riwayat/' . $t['reference_id']) ?>" style="color:inherit">
      <span class="tx-main"><b><?= e(kind_label($t['kind'])) ?></b><small class="mono"><?= e($t['reference_id']) ?> · <?= e(date('d M Y H:i', strtotime($t['created_at']))) ?></small></span>
      <span class="tx-amt"><?= rupiah((int)$t['total']) ?><small><?= status_badge($t['status']) ?></small></span>
    </a>
  <?php endforeach; ?>
</div>
