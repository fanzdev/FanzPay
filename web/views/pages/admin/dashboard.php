<?php
/**
 * Command Center — dashboard admin & super admin (elegan).
 * Hero sapaan + KPI + banner perhatian + grafik 14 hari +
 * rel aksi + tabel terbaru + panel pemilik (super saja).
 * Styling di app.css (prefix sa-). Data 100% dari controller.
 */
require_once fp_base_path('views/partials/icons.php');
/** @var array $s */ /** @var array $daily */ /** @var int $profit */ /** @var array $prov */ /** @var array $recent */ /** @var bool $is_super */ /** @var array $user */
$max = 1; $volTotal = 0; $byDate = [];
foreach ($daily as $d) { $max = max($max, (int)$d['v']); $volTotal += (int)$d['v']; $byDate[$d['d']] = $d; }
$todayKey = date('Y-m-d'); $yKey = date('Y-m-d', time() - 86400);
$volToday = (int)($byDate[$todayKey]['v'] ?? $s['vol_today']);
$volY = (int)($byDate[$yKey]['v'] ?? 0);
$delta = $volY > 0 ? round(($volToday - $volY) / $volY * 100) : ($volToday > 0 ? 100 : 0);
$avgDay = count($daily) > 0 ? (int)round($volTotal / count($daily)) : 0;
$provOk = !empty($prov['ok']);
$provMock = !empty($prov['data']['is_mock']);
$needAttention = (($s['pending'] ?? 0) > 0) || (($s['failed'] ?? 0) > 0);
$hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int)date('w')];
$bulanId = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][(int)date('n')];
$tglId = $hariId . ', ' . date('d') . ' ' . $bulanId . ' ' . date('Y');
$provMode = strtolower((string)\App\Services\Settings::get('provider_mode', 'sandbox'));
$depositOn = \App\Services\Settings::get('deposit_enabled', '1') === '1';
$mcpOn = \App\Services\Settings::get('mcp_enabled', '1') === '1';
$maintOn = \App\Services\Settings::maintenance();
$deltaCls = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
$deltaIco = $delta > 0 ? 'arrowUp' : ($delta < 0 ? 'arrowDown' : 'swap');
$deltaTxt = $delta > 0 ? '+' . $delta . '%' : ($delta < 0 ? $delta . '%' : '0%');
?>

<!-- HERO -->
<section class="sa-hero">
  <div>
    <span class="sa-kicker"><?= icon('spark', 14) ?> Command Center<?= $is_super ? ' · Super Admin' : ' · Admin' ?></span>
    <h2>Halo, <?= e(mb_substr($user['name'] ?? 'Admin', 0, 24)) ?> — ini ringkasan bisnismu hari ini.</h2>
    <p class="sa-meta"><span><?= e($tglId) ?></span><span>·</span><span><?= number_format($s['tx_today']) ?> transaksi</span><span>·</span><span><?= rupiah($volToday) ?> volume sukses</span><span class="sa-delta <?= $deltaCls ?>"><?= icon($deltaIco, 14) ?> <?= $deltaTxt ?> vs kemarin</span></p>
    <div class="sa-actions">
      <?php if (($s['pending'] ?? 0) > 0): ?><a class="btn btn-primary btn-sm" href="<?= url($panel . '/transaksi') ?>"><?= icon('zap', 15) ?> <?= number_format($s['pending']) ?> pending perlu tindakan</a>
      <?php else: ?><a class="btn btn-primary btn-sm" href="<?= url($panel . '/transaksi') ?>"><?= icon('checkCircle', 15) ?> Semua transaksi beres</a><?php endif; ?>
      <a class="btn btn-ghost btn-sm cmd-ghost" href="<?= url($panel . '/rekonsiliasi') ?>"><?= icon('refresh', 15) ?> Rekonsiliasi</a>
      <a class="btn btn-ghost btn-sm cmd-ghost" href="<?= url($panel . '/laporan') ?>"><?= icon('inbox', 15) ?> Laporan</a>
    </div>
  </div>
  <div class="sa-profit">
    <small><?= $is_super ? 'Estimasi profit PPOB hari ini' : 'Saldo provider DigiFlazz' ?></small>
    <b><?= $is_super ? rupiah($profit) : rupiah((int)($prov['data']['deposit'] ?? 0)) ?></b>
    <div class="row"><span class="badge <?= $provOk ? 'b-ok' : 'b-warn' ?>"><?= $provOk ? ($provMock ? 'Provider sandbox' : 'Provider terhubung') : 'Provider tidak merespons' ?></span><span class="badge <?= $maintOn ? 'b-warn' : 'b-ok' ?>"><?= $maintOn ? 'Maintenance' : 'Operasional' ?></span></div>
    <?php if ($is_super): ?><span class="fine">Profit = Σ (jual − modal) transaksi sukses hari ini · hanya super admin.</span><?php endif; ?>
  </div>
</section>

<!-- KPI -->
<div class="sa-kpis">
  <a class="sa-kpi" href="<?= url($panel . '/pengguna') ?>"><span class="sa-kpi-top"><span class="sa-kpi-ic"><?= icon('users', 19) ?></span>Total Pengguna</span><span class="sa-kpi-num"><?= number_format($s['users']) ?></span><span class="sa-kpi-sub">Akun role pelanggan</span></a>
  <a class="sa-kpi" href="<?= url($panel . '/transaksi') ?>"><span class="sa-kpi-top"><span class="sa-kpi-ic"><?= icon('swap', 19) ?></span>Transaksi Hari Ini</span><span class="sa-kpi-num"><?= number_format($s['tx_today']) ?></span><span class="sa-kpi-sub">Semua status · semua kanal</span></a>
  <a class="sa-kpi" href="<?= url($panel . '/laporan') ?>"><span class="sa-kpi-top"><span class="sa-kpi-ic"><?= icon('chart', 19) ?></span>Volume Sukses</span><span class="sa-kpi-num"><?= rupiah($volToday) ?></span><span class="sa-kpi-sub"><span class="sa-chip <?= $deltaCls ?>"><?= $deltaTxt ?> vs kemarin</span><span>Rata-rata <?= rupiah($avgDay) ?>/hari</span></span></a>
  <div class="sa-kpi"><span class="sa-kpi-top"><span class="sa-kpi-ic"><?= icon('wallet', 19) ?></span>Liabilitas Wallet</span><span class="sa-kpi-num"><?= rupiah((int)$s['liability']) ?></span><span class="sa-kpi-sub">Total saldo milik pengguna</span></div>
</div>

<!-- BANNER -->
<div class="sa-banner <?= $needAttention ? 'warn' : 'ok' ?>">
  <?= icon($needAttention ? 'bell' : 'checkCircle', 22) ?>
  <div class="grow"><b><?= $needAttention ? 'Perlu perhatian: ' . number_format($s['pending']) . ' pending · ' . number_format($s['failed']) . ' gagal hari ini' : 'Operasional sehat — tidak ada antrean.' ?></b><small><?= $needAttention ? 'Selesaikan pending di Deposit/Transaksi, lalu cek Rekonsiliasi untuk selisih.' : 'Semua transaksi terpantau. Pantau kembali secara berkala.' ?></small></div>
  <?php if ($needAttention): ?><div class="acts"><a class="btn btn-primary btn-sm" href="<?= url($panel . '/deposit') ?>">Buka Deposit</a><a class="btn btn-ghost btn-sm" href="<?= url($panel . '/rekonsiliasi') ?>">Rekonsiliasi</a></div><?php endif; ?>
</div>

<!-- GRAFIK + REL -->
<div class="sa-grid">
  <div class="panel" style="margin-bottom:0">
    <div class="dash-head"><span class="dash-ic"><?= icon('chart', 22) ?></span><div><h3>Volume 14 hari terakhir</h3><p>Total <?= rupiah($volTotal) ?> sukses · rata-rata <?= rupiah($avgDay) ?>/hari.</p></div><a class="btn btn-ghost btn-sm" style="margin-left:auto" href="<?= url($panel . '/laporan') ?>">Detail <?= icon('arrowR', 15) ?></a></div>
    <?php if (empty($daily)): ?><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada data volume.</p></div>
    <?php else: ?>
    <div class="sa-chart-wrap"><div class="sa-bars"><?php foreach ($daily as $d): $isToday = $d['d'] === $todayKey; ?><div class="sa-bar<?= $isToday ? ' today' : '' ?>" title="<?= e($d['d']) ?> · <?= (int)$d['c'] ?> transaksi · <?= rupiah((int)$d['v']) ?>"><i style="height:<?= max(4, round((int)$d['v'] / $max * 100)) ?>%"></i><span><?= e(date('d/m', strtotime($d['d']))) ?></span></div><?php endforeach; ?></div></div>
    <div class="sa-legend"><span><i class="sa-dot" style="background:var(--brand-500)"></i>Volume sukses harian</span><span><i class="sa-dot" style="background:#1090F0"></i>Hari ini</span><span>Arahkan kursor ke bar untuk jumlah transaksi.</span></div>
    <?php endif; ?>
  </div>
  <div class="sa-rail">
    <div class="panel" style="margin-bottom:0">
      <div class="dash-head"><span class="dash-ic"><?= icon('zap', 22) ?></span><div><h3>Aksi cepat</h3><p>Pintasan operasional harian.</p></div></div>
      <div class="sa-acts">
        <a href="<?= url($panel . '/transaksi') ?>"><?= icon('clock', 20) ?>Transaksi</a>
        <a href="<?= url($panel . '/deposit') ?>"><?= icon('plus', 20) ?>Deposit</a>
        <a href="<?= url($panel . '/withdraw') ?>"><?= icon('card', 20) ?>Withdraw</a>
        <a href="<?= url($panel . '/rekonsiliasi') ?>"><?= icon('refresh', 20) ?>Rekonsiliasi</a>
        <a href="<?= url($panel . '/produk') ?>"><?= icon('grid', 20) ?>Produk</a>
        <a href="<?= url($panel . '/pengguna') ?>"><?= icon('users', 20) ?>Pengguna</a>
        <a href="<?= url($panel . '/laporan') ?>"><?= icon('inbox', 20) ?>Laporan</a>
        <a href="<?= url($panel . '/audit') ?>"><?= icon('search', 20) ?>Audit</a>
      </div>
    </div>
    <div class="panel" style="margin-bottom:0">
      <div class="dash-head"><span class="dash-ic"><?= icon('shieldCheck', 22) ?></span><div><h3>Kesehatan sistem</h3><p>Status konfigurasi saat ini.</p></div></div>
      <ul class="sa-health">
        <li><i class="dot <?= $provOk ? 'g' : 'r' ?>"></i><b>Provider <?= e(strtoupper($provMode)) ?></b><span><?= $provOk ? ($provMock ? 'Sandbox' : 'Terhubung') : 'Tidak merespons' ?></span></li>
        <li><i class="dot <?= $depositOn ? 'g' : 'a' ?>"></i><b>Deposit QRIS</b><span><?= $depositOn ? 'Aktif' : 'Nonaktif' ?></span></li>
        <li><i class="dot <?= $mcpOn ? 'g' : 'a' ?>"></i><b>MCP Server</b><span><?= $mcpOn ? 'Aktif' : 'Nonaktif' ?></span></li>
        <li><i class="dot <?= $maintOn ? 'a' : 'g' ?>"></i><b>Mode maintenance</b><span><?= $maintOn ? 'Aktif' : 'Mati' ?></span></li>
      </ul>
    </div>
  </div>
</div>

<!-- TERBARU -->
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('receipt', 22) ?></span><div><h3>Transaksi terbaru</h3><p>10 aktivitas terakhir semua pengguna.</p></div><a class="btn btn-ghost btn-sm" style="margin-left:auto" href="<?= url($panel . '/transaksi') ?>">Semua <?= icon('arrowR', 15) ?></a></div>
  <?php if (empty($recent)): ?><div class="empty"><?= icon('inbox', 28) ?><p>Belum ada transaksi.</p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="tbl sa-tbl">
    <tr><th>Referensi</th><th>Pengguna</th><th>Jenis</th><th class="num">Total</th><th>Status</th><th>Waktu</th></tr>
    <?php foreach ($recent as $t): ?>
      <tr><td class="ref" title="<?= e($t['reference_id']) ?>"><?= e($t['reference_id']) ?></td><td><?= e($t['name']) ?></td><td><?= e(kind_label($t['kind'])) ?></td><td class="num"><?= rupiah((int)$t['total']) ?></td><td><?= status_badge($t['status']) ?></td><td style="white-space:nowrap"><?= e(date('d/m H:i', strtotime($t['created_at']))) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>

<?php if ($is_super): ?>
<!-- PEMILIK -->
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('shieldCheck', 22) ?></span><div><h3>Kontrol pemilik</h3><p>Konfigurasi tingkat sistem — perubahan tercatat di audit log.</p></div></div>
  <div class="sa-owner-grid">
    <a href="<?= url($panel . '/produk') ?>"><?= icon('tag', 20) ?><span><b>Produk &amp; Harga</b><small>Margin dan katalog DigiFlazz</small></span></a>
    <a href="<?= url($panel . '/admin') ?>"><?= icon('shield', 20) ?><span><b>Kelola Admin</b><small>Akun tim dan permission</small></span></a>
    <a href="<?= url($panel . '/pengaturan') ?>"><?= icon('gear', 20) ?><span><b>Pengaturan</b><small>Limit, biaya, dan mode</small></span></a>
    <a href="<?= url($panel . '/api') ?>"><?= icon('zap', 20) ?><span><b>API Config</b><small>Kredensial provider</small></span></a>
    <a href="<?= url($panel . '/mcp') ?>"><?= icon('headset', 20) ?><span><b>MCP Server</b><small>Akses AI agentic</small></span></a>
    <a href="<?= url($panel . '/notifikasi') ?>"><?= icon('bell', 20) ?><span><b>Notifikasi</b><small>Broadcast pengguna</small></span></a>
  </div>
</div>
<?php endif; ?>
