<?php
require_once fp_base_path('views/partials/icons.php');
/** @var array $user */ /** @var int $balance */ /** @var array $recent */ /** @var array $featured */ /** @var bool $sandbox */
?>
<?php if ($sandbox): ?><div class="sandbox-note"><?= icon('zap', 16) ?> Mode sandbox aktif — eksplorasi bebas, belum pakai uang asli. Gaspol buat belajar.</div><?php endif; ?>
<?php if (!($has_pin ?? true)): ?><div class="alert err">Satu langkah lagi, bestie 🔐 — buat <a href="<?= url('pin') ?>"><strong>PIN 6 digit</strong></a> untuk mengaktifkan transfer, withdraw &amp; pembelian.</div><?php endif; ?>

<section class="bal" aria-label="Saldo">
  <div class="lbl">Halo, <?= e($user['name']) ?> 💜 — Saldo FanzPay kamu</div>
  <div class="amt"><?= rupiah($balance) ?></div>
  <div class="row">
    <a class="qa" href="<?= url('deposit') ?>"><?= icon('plus', 18) ?>Deposit</a>
    <a class="qa" href="<?= url('transfer') ?>"><?= icon('swap', 18) ?>Transfer</a>
    <a class="qa" href="<?= url('withdraw') ?>"><?= icon('card', 18) ?>Withdraw</a>
    <a class="qa" href="<?= url('riwayat') ?>"><?= icon('clock', 18) ?>Riwayat</a>
  </div>
</section>

<div class="panel">
  <h3>Produk populer</h3>
  <div class="quick">
    <a href="<?= url('produk?cat=pulsa') ?>"><?= icon('phone') ?>Pulsa</a>
    <a href="<?= url('produk?cat=data') ?>"><?= icon('wifi') ?>Data</a>
    <a href="<?= url('produk?cat=pln') ?>"><?= icon('zap') ?>PLN</a>
    <a href="<?= url('produk?cat=game') ?>"><?= icon('game') ?>Game</a>
  </div>
</div>

<div class="panel">
  <h3>Transaksi terakhir</h3>
  <?php if (empty($recent)): ?>
    <div class="empty"><?= icon('inbox', 28) ?><p>Belum ada transaksi.<br><a href="<?= url('deposit') ?>">Deposit pertama</a> untuk memulai.</p></div>
  <?php else: ?>
    <?php foreach ($recent as $t): ?>
      <a class="tx-row" href="<?= url('riwayat/' . $t['reference_id']) ?>" style="color:inherit">
        <span class="tx-ic"><?= icon($t['kind'] === 'deposit' ? 'plus' : ($t['kind'] === 'transfer' ? 'swap' : ($t['kind'] === 'withdraw' ? 'card' : 'grid')), 18) ?></span>
        <span class="tx-main"><b><?= e(kind_label($t['kind']) . ' • ' . $t['reference_id']) ?></b><small><?= e(date('d M Y H:i', strtotime($t['created_at']))) ?></small></span>
        <span class="tx-amt"><?= rupiah((int)$t['total']) ?><small><?= status_badge($t['status']) ?></small></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
