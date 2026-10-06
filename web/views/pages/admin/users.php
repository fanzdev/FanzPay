<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var string $q */ /** @var bool $can_manage */
?>
<?php if (flash('ok')): ?><div class="alert ok"><?= e(flash('ok')) ?></div><?php endif; ?>
<div class="panel">
  <form method="get" action="<?= url('admin/pengguna') ?>" style="display:flex;gap:8px;margin-bottom:12px">
    <input name="q" value="<?= e($q) ?>" placeholder="Cari nama / email / HP" style="flex:1;font:400 14px var(--font);border:1px solid var(--line);border-radius:10px;padding:10px 12px">
    <button class="btn btn-ghost btn-sm" type="submit">Cari</button>
  </form>
  <div class="table-wrap"><table class="tbl">
    <tr><th>User</th><th>Kontak</th><th>Saldo</th><th>Status</th><?php if ($can_manage): ?><th>Aksi</th><?php endif; ?></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?= e($r['name']) ?></b><br><small class="mono" style="color:var(--mut)"><?= e($r['public_id']) ?></small></td>
        <td><?= e($r['email']) ?><br><small><?= e($r['phone']) ?></small></td>
        <td class="mono"><?= rupiah((int)($r['balance'] ?? 0)) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <?php if ($can_manage): ?><td>
          <form method="post" action="<?= url('admin/pengguna/' . $r['id'] . '/toggle') ?>" data-confirm="Ubah status akun ini?"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= $r['status'] === 'active' ? 'Tangguhkan' : 'Aktifkan' ?></button></form>
        </td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </table></div>
</div>
