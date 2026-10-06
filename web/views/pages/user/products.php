<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $cats */ /** @var array $items */ /** @var string|null $active_cat */
?>
<div class="chips">
  <a class="chip <?= !$active_cat ? 'on' : '' ?>" href="<?= url('produk') ?>">Semua</a>
  <?php foreach ($cats as $c): ?>
    <a class="chip <?= $active_cat === $c['code'] ? 'on' : '' ?>" href="<?= url('produk?cat=' . $c['code']) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php if (empty($items)): ?><div class="panel"><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada produk di kategori ini.</p></div></div><?php endif; ?>
<div class="prod-grid">
  <?php foreach ($items as $it): ?>
    <a class="prod" href="<?= url('produk/' . $it['code']) ?>"><b><?= e($it['name']) ?><br><small style="color:var(--mut)"><?= e($it['cat_name']) ?></small></b><span><?= rupiah((int)$it['price']) ?></span></a>
  <?php endforeach; ?>
</div>
