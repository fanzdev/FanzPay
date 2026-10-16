<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $cats */ /** @var array $items */ /** @var string|null $active_cat */
?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('grid', 22) ?></span><div><h3>Katalog produk</h3><p><?= !empty($items) ? count($items) . ' produk tampil' : 'Pilih kategori untuk mulai' ?><?= $active_cat ? ' · ' . e($active_cat) : ' · semua kategori' ?> · harga final.</p></div></div>
<div class="chips">
  <a class="chip <?= !$active_cat ? 'on' : '' ?>" href="<?= url('produk') ?>">Semua</a>
  <?php foreach ($cats as $c): ?>
    <a class="chip <?= $active_cat === $c['code'] ? 'on' : '' ?>" href="<?= url('produk?cat=' . $c['code']) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>
  <div class="info-note"><?= icon('tag', 18) ?><span>Harga yang tampil adalah <strong>harga final</strong> — saldo terpotong persis segitu, tanpa biaya tersembunyi.</span></div>
</div>
<?php if (empty($items)): ?><div class="panel"><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada produk di kategori ini.</p></div></div><?php endif; ?>
<div class="prod-grid">
  <?php foreach ($items as $it): ?>
    <a class="prod" href="<?= url('produk/' . $it['code']) ?>"><span class="prod-tx"><b><?= e($it['name']) ?></b><small><?= e($it['cat_name']) ?></small></span><span class="prod-price"><?= rupiah((int)$it['price']) ?></span></a>
  <?php endforeach; ?>
</div>
