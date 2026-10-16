<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var string $q */ /** @var string $status */
/** @var int $page */ /** @var int $pages */ /** @var bool $has_gateway */
/** @var string|null $ok */ /** @var string|null $error */
?>
<?php if (!empty($ok)): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <form method="get" action="<?= url($panel . '/deposit') ?>" style="display:flex;gap:8px;flex-wrap:wrap">
    <input name="q" value="<?= e($q ?? '') ?>" placeholder="Cari referensi / nama / email" style="flex:2;min-width:200px">
    <select name="status"><option value="">Semua status</option>
      <?php foreach (['pending' => 'Menunggu', 'success' => 'Berhasil', 'expired' => 'Kedaluwarsa', 'failed' => 'Gagal'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= ($status ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-ghost" type="submit">Filter</button>
  </form>
</div>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Referensi</th><th>User</th><th>Metode</th><th>Deposit</th><th>Biaya</th><th>Total bayar</th><th>Status</th><th>Kedaluwarsa</th><th>Aksi</th></tr>
  <?php if (empty($rows)): ?><tr><td colspan="9" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
  <?php foreach ($rows as $d): ?>
    <tr>
      <td class="mono"><?= e($d['reference_id']) ?><br><small class="mono" style="color:var(--mut)"><?= e((string)($d['provider_txn_id'] ?? '')) ?></small><?php if (!empty($d['is_mock'])): ?> <small class="badge b-warn">mock</small><?php endif; ?></td>
      <td><?= e($d['name']) ?></td>
      <td><small><?= e(($has_gateway ? (($d['gateway'] ?? 'midtrans') . ' · ') : '') . ($d['payment_type'] ?? 'qris')) ?></small><?php if (!empty($d['payment_code'])): ?><br><small class="mono"><?= e((string)$d['payment_code']) ?></small><?php endif; ?></td>
      <td class="mono"><?= rupiah((int)$d['amount']) ?></td>
      <td class="mono"><?= rupiah((int)($d['fee'] ?? 0)) ?></td>
      <td class="mono"><?= rupiah((int)$d['total_amount']) ?></td>
      <td><?= status_badge($d['status']) ?></td>
      <td><small><?= e($d['expired_at'] ?? '-') ?></small><?php if (!empty($d['paid_at'])): ?><br><small>bayar <?= e($d['paid_at']) ?></small><?php endif; ?><?php if (!empty($d['credited_at'])): ?><br><small>kredit <?= e($d['credited_at']) ?></small><?php endif; ?></td>
      <td style="white-space:nowrap">
        <?php if (($d['status'] ?? '') === 'pending' && empty($d['is_mock'])): ?>
          <form method="post" action="<?= url($panel . '/deposit/' . $d['reference_id'] . '/sync') ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Sinkron</button></form>
        <?php endif; ?>
        <?php if (($d['status'] ?? '') === 'pending' && !empty($d['is_mock'])): ?>
          <form method="post" action="<?= url($panel . '/deposit/' . $d['reference_id'] . '/simulate') ?>" style="display:inline" data-confirm="Simulasi sukses untuk deposit MOCK ini? Saldo user bertambah."><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Simulasi sukses</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php if (($pages ?? 1) > 1): ?>
  <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
    <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= url($panel . '/deposit?q=' . urlencode($q ?? '') . '&status=' . urlencode($status ?? '') . '&page=' . ($page - 1)) ?>">← Sebelumnya</a><?php endif; ?>
    <small style="color:var(--mut)">Halaman <?= $page ?> / <?= $pages ?></small>
    <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= url($panel . '/deposit?q=' . urlencode($q ?? '') . '&status=' . urlencode($status ?? '') . '&page=' . ($page + 1)) ?>">Berikutnya →</a><?php endif; ?>
  </div>
<?php endif; ?>
</div>
