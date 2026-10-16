<?php /** @var array $p */ /** @var string|null $error */ /** @var string|null $ok */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('tag', 22) ?></span><div><h3><?= e($p['name']) ?></h3><p><?= e($p['cat_name']) ?> · Harga final</p></div></div>
  <p class="price-big"><?= rupiah((int)$p['sell_price']) ?></p>
  <form method="post" action="<?= url('ppob/beli') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="code" value="<?= e($p['code']) ?>">
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="customer_no">Nomor tujuan</label><input id="customer_no" name="customer_no" required inputmode="numeric" placeholder="08… / ID pelanggan"></div>
    <div class="field"><label for="pin">PIN transaksi (6 digit)</label><input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="••••••"></div>
    <button class="btn btn-primary btn-block" type="submit">Bayar <?= rupiah((int)$p['sell_price']) ?></button>
  </form>
  <ol class="guide">
    <li><span class="g-num">1</span><span><b>Isi nomor tujuan</b> dengan teliti — pulsa/ID yang salah tidak bisa dibatalkan.</span></li>
    <li><span class="g-num">2</span><span><b>Konfirmasi dengan PIN</b> 6 digit milikmu.</span></li>
    <li><span class="g-num">3</span><span><b>Pantau status</b> di <a href="<?= url('riwayat') ?>"><strong>Riwayat</strong></a> — gagal otomatis jadi refund.</span></li>
  </ol>
</div>
