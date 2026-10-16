<?php /** @var array $rows */ ?>
<div class="panel"><h3>Laporan 30 hari</h3><div class="table-wrap"><table class="tbl">
  <tr><th>Tanggal</th><th>Jenis</th><th>Transaksi</th><th>Volume sukses</th></tr>
  <?php if (empty($rows)): ?><tr><td colspan="4" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['d']) ?></td><td><?= e(kind_label($r['kind'])) ?></td><td class="mono"><?= number_format((int)$r['c']) ?></td><td class="mono"><?= rupiah((int)$r['v']) ?></td></tr>
  <?php endforeach; ?>
</table></div></div>
