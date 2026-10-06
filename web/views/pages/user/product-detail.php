<?php /** @var array $p */ /** @var string|null $error */ /** @var string|null $ok */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3><?= e($p['name']) ?></h3>
  <p style="color:var(--mut);font-size:14px"><?= e($p['cat_name']) ?> · Harga final</p>
  <p style="font-size:28px;font-weight:700;color:var(--brand-700)"><?= rupiah((int)$p['sell_price']) ?></p>
  <form method="post" action="<?= url('ppob/beli') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="code" value="<?= e($p['code']) ?>">
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="customer_no">Nomor tujuan</label><input id="customer_no" name="customer_no" required inputmode="numeric" placeholder="08… / ID pelanggan"></div>
    <div class="field"><label for="pin">PIN transaksi (6 digit)</label><input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="••••••"></div>
    <button class="btn btn-primary btn-block" type="submit">Bayar <?= rupiah((int)$p['sell_price']) ?></button>
  </form>
</div>
