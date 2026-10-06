<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $rows */ /** @var array $cats */ /** @var bool $can_manage */ /** @var bool $is_super */
?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err = flash('error')): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<div class="panel" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <b> Sinkronisasi katalog DigiFlazz</b>
  <form method="post" action="<?= url('admin/produk/sinkron') ?>"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Sinkron sekarang</button></form>
</div>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Produk</th><th>Kategori</th><?php if ($is_super): ?><th>Modal</th><th>Margin</th><?php endif; ?><th>Harga jual</th><th>Status</th><?php if ($can_manage): ?><th>Ubah harga</th><?php endif; ?></tr>
  <?php foreach ($rows as $p): ?>
    <tr>
      <td><b><?= e($p['name']) ?></b><br><small class="mono" style="color:var(--mut)"><?= e($p['code']) ?></small></td>
      <td><?= e($p['cat_name']) ?></td>
      <?php if ($is_super): ?><td class="mono"><?= $p['base_price'] !== null ? rupiah((int)$p['base_price']) : '—' ?></td><td class="mono"><?= $p['margin'] !== null ? rupiah((int)$p['margin']) : '—' ?></td><?php endif; ?>
      <td class="mono"><b><?= $p['sell_price'] !== null ? rupiah((int)$p['sell_price']) : '—' ?></b></td>
      <td><?= status_badge($p['is_active'] ? 'active' : 'suspended') ?></td>
      <?php if ($can_manage): ?><td style="white-space:nowrap">
        <form method="post" action="<?= url('admin/produk/harga') ?>" style="display:inline-flex;gap:6px"><?= \App\Core\CSRF::field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <input name="sell_price" type="number" min="1000" value="<?= (int)($p['sell_price'] ?? 0) ?>" style="width:110px;font:400 13px var(--font);border:1px solid var(--line);border-radius:8px;padding:7px 9px">
          <button class="btn btn-ghost btn-sm" type="submit">OK</button>
        </form>
        <form method="post" action="<?= url('admin/produk/' . $p['id'] . '/toggle') ?>" style="display:inline"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit"><?= $p['is_active'] ? 'Nonaktif' : 'Aktif' ?></button></form>
      </td><?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table></div></div>
