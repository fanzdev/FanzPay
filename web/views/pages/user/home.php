<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $user */ /** @var int $balance */ /** @var array $recent */ /** @var array $featured */ /** @var bool $sandbox */
?>
<?php if ($sandbox): ?><div class="sandbox-note"><?= icon('zap', 16) ?> Mode sandbox aktif — cocok untuk uji coba, belum memakai dana sebenarnya.</div><?php endif; ?>
<?php if (!($has_pin ?? true)): ?><div class="alert err">Satu langkah lagi — buat <a href="<?= url('pin') ?>"><strong>PIN 6 digit</strong></a> untuk mengaktifkan transfer, withdraw &amp; pembelian.</div><?php endif; ?>

<section class="bal" aria-label="Saldo">
  <div class="lbl">Halo, <?= e($user['name']) ?> — Saldo FanzPay kamu</div>
  <div class="amt"><?= rupiah($balance) ?></div>
  <div class="row">
    <a class="qa" href="<?= url('deposit') ?>"><?= icon('plus', 18) ?>Deposit</a>
    <a class="qa" href="<?= url('transfer') ?>"><?= icon('swap', 18) ?>Transfer</a>
    <a class="qa" href="<?= url('withdraw') ?>"><?= icon('card', 18) ?>Withdraw</a>
    <a class="qa" href="<?= url('riwayat') ?>"><?= icon('clock', 18) ?>Riwayat</a>
  </div>
</section>

<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('grid', 22) ?></span><div><h3>Produk populer</h3><p>Delapan layanan dalam satu saldo.</p></div></div>
  <div class="quick">
    <a href="<?= url('produk?cat=pulsa') ?>"><?= icon('phone') ?>Pulsa</a>
    <a href="<?= url('produk?cat=data') ?>"><?= icon('wifi') ?>Data</a>
    <a href="<?= url('produk?cat=pln') ?>"><?= icon('zap') ?>PLN</a>
    <a href="<?= url('produk?cat=game') ?>"><?= icon('game') ?>Game</a>
  </div>
  <div class="quick">
    <a href="<?= url('produk?cat=bpjs') ?>"><?= icon('shieldCheck') ?>BPJS</a>
    <a href="<?= url('produk?cat=emoney') ?>"><?= icon('wallet') ?>E-Money</a>
    <a href="<?= url('produk') ?>"><?= icon('grid') ?>Semua</a>
    <a href="<?= url('qr') ?>"><?= icon('qr') ?>QR Saya</a>
  </div>
</div>

<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('clock', 22) ?></span><div><h3>Transaksi terakhir</h3><p>5 aktivitas terbaru di akunmu.</p></div></div>
  <?php if (empty($recent)): ?>
    <div class="empty"><?= icon('inbox', 28) ?><p>Belum ada transaksi.<br><a href="<?= url('deposit') ?>">Deposit pertama</a> untuk memulai.</p></div>
  <?php else: ?>
    <?php foreach ($recent as $t): ?>
      <a class="tx-row" href="<?= url('riwayat/' . $t['reference_id']) ?>">
        <span class="tx-ic"><?= icon($t['kind'] === 'deposit' ? 'plus' : ($t['kind'] === 'transfer' ? 'swap' : ($t['kind'] === 'withdraw' ? 'card' : 'grid')), 18) ?></span>
        <span class="tx-main"><b><?= e(kind_label($t['kind']) . ' • ' . $t['reference_id']) ?></b><small><?= e(date('d M Y H:i', strtotime($t['created_at']))) ?></small></span>
        <span class="tx-amt"><?= rupiah((int)$t['total']) ?><small><?= status_badge($t['status']) ?></small></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('spark', 22) ?></span><div><h3>Mulai dalam 3 langkah</h3><p>Alur tercepat memakai FanzPay.</p></div></div>
  <ol class="guide">
    <li><span class="g-num">1</span><span><b>Deposit via QRIS</b> — saldo masuk otomatis setelah terbayar.</span></li>
    <li><span class="g-num">2</span><span><b>Buat PIN 6 digit</b> — wajib untuk transfer, tarik dana, dan belanja.</span></li>
    <li><span class="g-num">3</span><span><b>Beli &amp; bayar</b> — pulsa, PLN, BPJS, e-money, dan game dari satu saldo.</span></li>
  </ol>
  <div class="info-note"><?= icon('shieldCheck', 18) ?><span>Semua mutasi tercatat di ledger dan bisa ditelusuri di <a href="<?= url('riwayat') ?>"><strong>Riwayat Transaksi</strong></a>.</span></div>
</div>
