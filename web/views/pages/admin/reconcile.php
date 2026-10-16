<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $data */
/** $data: pending_by_kind, old_pending, webhooks_open, balance_mismatch, midtrans_pending, midtrans_mode, midtrans_ready */
?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="stat-grid" style="grid-template-columns:1fr 1fr 1fr">
  <div class="stat"><small>Pending &gt; 30 menit</small><b class="mono"><?= count($data['old_pending']) ?></b></div>
  <div class="stat"><small>Midtrans pending</small><b class="mono"><?= count($data['midtrans_pending'] ?? []) ?></b></div>
  <div class="stat"><small>Selisih saldo vs ledger</small><b class="mono"><?= count($data['balance_mismatch']) ?></b></div>
</div>
<div class="panel">
  <h3>Status Midtrans</h3>
  <p style="color:var(--mut)">Mode <b class="mono"><?= e($data['midtrans_mode'] ?? 'sandbox') ?></b> · <?= !empty($data['midtrans_ready']) ? 'Server Key terpasang.' : 'Server Key BELUM dipasang — deposit berjalan dalam mode simulasi sandbox.' ?></p>
</div>
<?php if (!empty($data['midtrans_pending'])): ?>
<div class="panel"><h3>Deposit Midtrans pending — bandingkan dengan dashboard Midtrans</h3>
  <div class="table-wrap"><table class="tbl">
    <tr><th>Order ID</th><th>Metode</th><th>Deposit</th><th>Total</th><th>Dibuat</th><th>Kedaluwarsa</th><th>Aksi</th></tr>
    <?php foreach ($data['midtrans_pending'] as $t): ?>
      <tr><td class="mono"><?= e($t['reference_id']) ?><?php if (!empty($t['is_mock'])): ?> <small class="badge b-warn">mock</small><?php endif; ?></td>
        <td><small><?= e($t['payment_type'] ?? '-') ?></small></td>
        <td class="mono"><?= rupiah((int)($t['amount'] ?? $t['total'] ?? 0)) ?></td>
        <td class="mono"><?= rupiah((int)($t['total_amount'] ?? $t['total'] ?? 0)) ?></td>
        <td><small><?= e($t['created_at'] ?? '-') ?></small></td><td><small><?= e($t['expired_at'] ?? '-') ?></small></td>
        <td><?php if (empty($t['is_mock'])): ?><form method="post" action="<?= url($panel . '/deposit/' . $t['reference_id'] . '/sync') ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Cek ke Midtrans</button></form><?php else: ?><small style="color:var(--mut)">mock — pakai Simulasi di halaman Deposit</small><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <p style="color:var(--mut);font-size:13px">Jangan kredit ulang manual dari halaman ini. Koreksi hanya via status resmi Midtrans (tombol di atas) atau mekanisme refund/adjustment tercatat.</p>
</div>
<?php endif; ?>
<div class="panel"><h3>Pending lama — butuh cek ke provider</h3>
  <?php if (empty($data['old_pending'])): ?><p style="color:var(--mut)">Bersih. Tidak ada transaksi menggantung.</p><?php endif; ?>
  <?php foreach ($data['old_pending'] as $t): ?>
    <div class="tx-row"><span class="tx-main"><b class="mono"><?= e($t['reference_id']) ?></b><small><?= e($t['kind']) ?> · <?= rupiah((int)$t['total']) ?> · <?= e($t['created_at']) ?></small></span>
    <?php if ($t['kind'] === 'ppob'): ?><form method="post" action="<?= url($panel . '/rekonsiliasi/' . $t['reference_id'] . '/refund') ?>" data-confirm="Refund transaksi ini?"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Refund</button></form><?php endif; ?></div>
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
