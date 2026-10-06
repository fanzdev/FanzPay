<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $s */ /** @var array $daily */ /** @var int $profit */ /** @var array $prov */ /** @var array $recent */ /** @var bool $is_super */
$max = 1;
foreach ($daily as $d) $max = max($max, (int)$d['v']);
?>
<div class="stat-grid">
  <div class="stat"><small>Pengguna</small><b class="mono"><?= number_format($s['users']) ?></b></div>
  <div class="stat"><small>Transaksi hari ini</small><b class="mono"><?= number_format($s['tx_today']) ?></b></div>
  <div class="stat"><small>Volume hari ini</small><b class="mono"><?= rupiah((int)$s['vol_today']) ?></b></div>
  <div class="stat"><small>Liabilitas wallet</small><b class="mono"><?= rupiah((int)$s['liability']) ?></b></div>
</div>
<div class="stat-grid" style="grid-template-columns:1fr 1fr 1fr">
  <div class="stat"><small>Pending perlu tindakan</small><b class="mono"><?= number_format($s['pending']) ?></b></div>
  <div class="stat"><small>Gagal hari ini</small><b class="mono"><?= number_format($s['failed']) ?></b></div>
  <div class="stat"><small><?= $is_super ? 'Estimasi profit PPOB hari ini' : 'Saldo provider' ?></small><b class="mono"><?= $is_super ? rupiah($profit) : rupiah((int)($prov['data']['deposit'] ?? 0)) ?></b></div>
</div>
<div class="panel">
  <h3>Volume 14 hari terakhir</h3>
  <div class="bars"><?php foreach ($daily as $d): ?><i style="height:<?= max(4, round((int)$d['v'] / $max * 100)) ?>%" title="<?= e($d['d']) ?>: <?= rupiah((int)$d['v']) ?>"></i><?php endforeach; ?></div>
  <div class="bars-lbl"><?php foreach ($daily as $d): ?><span><?= e(date('d/m', strtotime($d['d']))) ?></span><?php endforeach; ?></div>
</div>
<div class="panel">
  <h3>Transaksi terbaru</h3>
  <div class="table-wrap"><table class="tbl">
    <tr><th>Referensi</th><th>User</th><th>Jenis</th><th>Total</th><th>Status</th></tr>
    <?php foreach ($recent as $t): ?>
      <tr><td class="mono"><?= e($t['reference_id']) ?></td><td><?= e($t['name']) ?></td><td><?= e(kind_label($t['kind'])) ?></td><td class="mono"><?= rupiah((int)$t['total']) ?></td><td><?= status_badge($t['status']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
