<?php /** @var string|null $ok */ /** @var string|null $error */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Kirim pengumuman ke semua pengguna</h3>
  <form method="post" action="<?= url($panel . '/notifikasi') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="field"><label>Judul</label><input name="title" required maxlength="150"></div>
    <div class="field"><label>Isi</label><input name="body" required maxlength="250"></div>
    <button class="btn btn-primary" type="submit">Kirim</button>
  </form>
</div>
