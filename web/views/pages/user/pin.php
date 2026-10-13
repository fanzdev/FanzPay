<?php /** @var string|null $error */ /** @var string|null $ok */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel" style="text-align:center">
  <div class="dash-head" style="justify-content:center;text-align:left"><span class="dash-ic"><?= icon('lock', 22) ?></span><div><h3>PIN transaksi</h3><p>6 digit untuk transfer, withdraw &amp; pembelian.</p></div></div>
  <form method="post" action="<?= url('pin') ?>">
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="pin" id="pin-val" value="">
    <div class="pin-dots" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <div data-pinpad="#pin-val" class="pinpad">
      <?php foreach (['1','2','3','4','5','6','7','8','9','del','0','C'] as $k): ?>
        <?php if ($k === 'C'): ?><button type="button" onclick="location.reload()">C</button>
        <?php else: ?><button type="button" data-k="<?= $k === 'del' ? 'del' : $k ?>"><?= $k === 'del' ? '⌫' : $k ?></button><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <div style="height:14px"></div>
    <button class="btn btn-primary btn-block" type="submit">Simpan PIN</button>
  </form>
  <div class="info-note" style="text-align:left"><?= icon('shieldCheck', 18) ?><span>PIN <strong>disimpan sebagai hash</strong> — tidak bisa dibaca siapa pun. Bedakan dengan kata sandi login.</span></div>
</div>
