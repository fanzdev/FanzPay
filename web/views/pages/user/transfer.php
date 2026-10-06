<?php /** @var string|null $error */ /** @var string|null $ok */ /** @var string $lookup */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Transfer gratis, tanpa biaya</h3>
  <p style="color:var(--mut);font-size:14px">Ke sesama pengguna via nomor HP atau QR FanzPay. Dana masuk seketika.</p>
  <form method="post" action="<?= url('transfer') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="lookup">Nomor HP / ID penerima</label><input id="lookup" name="lookup" required value="<?= e($lookup) ?>" placeholder="08… atau FPXXXXXXXX"></div>
    <div class="field"><label for="amount">Nominal</label><input id="amount" name="amount" type="number" min="1000" required placeholder="50000"></div>
    <div class="field"><label for="pin">PIN transaksi</label><input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="••••••"></div>
    <button class="btn btn-primary btn-block" type="submit">Kirim sekarang</button>
  </form>
</div>
