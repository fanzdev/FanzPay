<?php /** @var array $user */ /** @var int $balance */ /** @var string|null $error */ /** @var string|null $ok */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Profil</h3>
  <p style="color:var(--mut);font-size:14px"><?= e($user['email']) ?> · <?= e($user['phone']) ?></p>
  <form method="post" action="<?= url('profil') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="field"><label for="name">Nama</label><input id="name" name="name" required maxlength="100" value="<?= e($user['name']) ?>"></div>
    <button class="btn btn-ghost" type="submit">Simpan</button>
    <a class="btn btn-ghost" href="<?= url('pin') ?>">Kelola PIN</a>
    <a class="btn btn-ghost" href="<?= url('qr') ?>">QR Saya</a>
  </form>
</div>
<div class="panel">
  <h3>Dompet</h3>
  <p>Saldo saat ini <b><?= rupiah($balance) ?></b></p>
  <p style="color:var(--mut);font-size:13px">Setiap mutasi tercatat di ledger dan bisa direkonstruksi penuh.</p>
</div>
