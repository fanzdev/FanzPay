<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $s */ /** @var array $daily */ /** @var int $profit */ /** @var array $prov */ /** @var array $recent */ /** @var bool $is_super */ /** @var array $user */
$max = 1;
$volTotal = 0;
foreach ($daily as $d) { $max = max($max, (int)$d['v']); $volTotal += (int)$d['v']; }
$provOk = !empty($prov['ok']);
?>
<section class="cmd-hero">
  <div>
    <span class="eyebrow eyebrow-light"><?= icon('spark', 15) ?> Command Center<?= $is_super ? ' · Super Admin' : '' ?></span>
    <h2>Halo, <?= e(mb_substr($user['name'] ?? 'Admin', 0, 24)) ?> — ini ringkasan bisnismu hari ini.</h2>
    <p><?= date('l, d F Y') ?> · <?= number_format($s['tx_today']) ?> transaksi · volume <?= rupiah((int)$s['vol_today']) ?></p>
    <div class="cmd-actions">
      <?php if (($s['pending'] ?? 0) > 0): ?><a class="btn btn-primary btn-sm" href="<?= url('admin/transaksi') ?>"><?= icon('zap', 15) ?> <?= number_format($s['pending']) ?> pending perlu tindakan</a>
      <?php else: ?><a class="btn btn-primary btn-sm" href="<?= url('admin/transaksi') ?>"><?= icon('checkCircle', 15) ?> Semua transaksi beres</a><?php endif; ?>
      <a class="btn btn-ghost btn-sm cmd-ghost" href="<?= url('admin/rekonsiliasi') ?>"><?= icon('refresh', 15) ?> Rekonsiliasi</a>
      <a class="btn btn-ghost btn-sm cmd-ghost" href="<?= url('admin/laporan') ?>"><?= icon('inbox', 15) ?> Laporan</a>
    </div>
  </div>
  <div class="cmd-side">
    <div class="cmd-prov"><small><?= $is_super ? 'Estimasi profit PPOB hari ini' : 'Saldo provider DigiFlazz' ?></small><b><?= $is_super ? rupiah($profit) : rupiah((int)($prov['data']['deposit'] ?? 0)) ?></b><span class="badge <?= $provOk ? 'b-ok' : 'b-warn' ?>"><?= $provOk ? 'Provider terhubung' : 'Provider tidak merespons' ?></span></div>
  </div>
</section>

<div class="stat-grid">
  <a class="stat stat-link" href="<?= url('admin/pengguna') ?>"><span class="stat-ic"><?= icon('users', 20) ?></span><small>Total pengguna</small><b class="mono"><?= number_format($s['users']) ?></b></a>
  <a class="stat stat-link" href="<?= url('admin/transaksi') ?>"><span class="stat-ic"><?= icon('swap', 20) ?></span><small>Transaksi hari ini</small><b class="mono"><?= number_format($s['tx_today']) ?></b></a>
  <a class="stat stat-link" href="<?= url('admin/laporan') ?>"><span class="stat-ic"><?= icon('chart', 20) ?></span><small>Volume hari ini</small><b class="mono"><?= rupiah((int)$s['vol_today']) ?></b></a>
  <div class="stat"><span class="stat-ic"><?= icon('wallet', 20) ?></span><small>Liabilitas wallet</small><b class="mono"><?= rupiah((int)$s['liability']) ?></b></div>
</div>
<div class="stat-grid" style="grid-template-columns:1fr 1fr">
  <a class="stat stat-link <?= ($s['pending'] ?? 0) > 0 ? 'stat-alert' : '' ?>" href="<?= url('admin/deposit') ?>"><span class="stat-ic"><?= icon('clock', 20) ?></span><small>Pending perlu tindakan</small><b class="mono"><?= number_format($s['pending']) ?></b></a>
  <a class="stat stat-link <?= ($s['failed'] ?? 0) > 0 ? 'stat-alert' : '' ?>" href="<?= url('admin/transaksi') ?>"><span class="stat-ic"><?= icon('x', 20) ?></span><small>Gagal hari ini</small><b class="mono"><?= number_format($s['failed']) ?></b></a>
</div>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('chart', 22) ?></span><div><h3>Volume 14 hari terakhir</h3><p>Total <?= rupiah($volTotal) ?> transaksi sukses.</p></div><a class="btn btn-ghost btn-sm" style="margin-left:auto" href="<?= url('admin/laporan') ?>">Detail <?= icon('arrowR', 15) ?></a></div>
  <?php if (empty($daily)): ?><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada data volume.</p></div>
  <?php else: ?>
  <div class="bars"><?php foreach ($daily as $d): ?><i style="height:<?= max(4, round((int)$d['v'] / $max * 100)) ?>%" title="<?= e($d['d']) ?>: <?= rupiah((int)$d['v']) ?>"></i><?php endforeach; ?></div>
  <div class="bars-lbl"><?php foreach ($daily as $d): ?><span><?= e(date('d/m', strtotime($d['d']))) ?></span><?php endforeach; ?></div>
  <?php endif; ?>
</div>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('receipt', 22) ?></span><div><h3>Transaksi terbaru</h3><p>10 aktivitas terakhir semua pengguna.</p></div><a class="btn btn-ghost btn-sm" style="margin-left:auto" href="<?= url('admin/transaksi') ?>">Semua <?= icon('arrowR', 15) ?></a></div>
  <?php if (empty($recent)): ?><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada transaksi.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="tbl">
    <tr><th>Referensi</th><th>User</th><th>Jenis</th><th>Total</th><th>Status</th></tr>
    <?php foreach ($recent as $t): ?>
      <tr><td class="mono"><?= e($t['reference_id']) ?></td><td><?= e($t['name']) ?></td><td><?= e(kind_label($t['kind'])) ?></td><td class="mono"><?= rupiah((int)$t['total']) ?></td><td><?= status_badge($t['status']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php if ($is_super): ?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('shieldCheck', 22) ?></span><div><h3>Aksi super admin</h3><p>Pintasan operasional tingkat pemilik.</p></div></div>
  <div class="quick" style="grid-template-columns:repeat(4,1fr)">
    <a href="<?= url('admin/produk') ?>"><?= icon('grid') ?>Produk &amp; Harga</a>
    <a href="<?= url('admin/admin') ?>"><?= icon('shield') ?>Kelola Admin</a>
    <a href="<?= url('admin/audit') ?>"><?= icon('search') ?>Audit Log</a>
    <a href="<?= url('admin/api') ?>"><?= icon('zap') ?>API Config</a>
  </div>
  <div class="info-note"><?= icon('lock', 18) ?><span>Angka profit hanya terlihat oleh <strong>super admin</strong> dan dihitung dari selisih harga jual–modal transaksi sukses hari ini.</span></div>
</div>
<?php endif; ?>
