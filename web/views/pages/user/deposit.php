<?php /** @var string|null $error */ /** @var int $min */ /** @var int $max */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <h3>Deposit via QRIS</h3>
  <p style="color:var(--mut);font-size:14px">Minimal <?= rupiah($min) ?> · maksimal <?= rupiah($max) ?>. QR dibuat di server &amp; status diverifikasi otomatis.</p>
  <form method="post" action="<?= url('deposit') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="amount">Nominal deposit</label><input id="amount" name="amount" type="number" min="<?= $min ?>" max="<?= $max ?>" required placeholder="50000"></div>
    <div class="grid2">
      <?php foreach ([50000, 100000, 250000, 500000] as $q): ?>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('amount').value=<?= $q ?>"><?= rupiah($q) ?></button>
      <?php endforeach; ?>
    </div>
    <div style="height:12px"></div>
    <button class="btn btn-primary btn-block" type="submit">Buat QRIS</button>
  </form>
</div>
