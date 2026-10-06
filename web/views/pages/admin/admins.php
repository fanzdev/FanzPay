<?php /** @var array $rows */ /** @var array $permsOf */ /** @var array $perms */ ?>
<?php if ($err = flash('error')): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Buat admin baru</h3>
  <form method="post" action="<?= url('admin/admin/buat') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Nama</label><input name="name" required maxlength="100"></div>
      <div class="field"><label>Email</label><input name="email" type="email" required></div>
    </div>
    <div class="field"><label>Kata sandi awal</label><input name="password" type="text" required minlength="8"></div>
    <div class="field"><label>Permission awal</label><div class="perm-grid">
      <?php foreach ($perms as $p): ?><label class="perm"><input type="checkbox" name="perms[]" value="<?= e($p) ?>"> <?= e($p) ?></label><?php endforeach; ?>
    </div></div>
    <button class="btn btn-primary" type="submit">Buat admin</button>
  </form>
</div>
<?php foreach ($rows as $r): ?>
<div class="panel">
  <h3><?= e($r['name']) ?> <small style="color:var(--mut)">· <?= e($r['role']) ?> · <?= e($r['email']) ?></small></h3>
  <?php if ($r['role'] === 'admin'): ?>
  <form method="post" action="<?= url('admin/admin/' . $r['id'] . '/perms') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="perm-grid">
      <?php foreach ($perms as $p): ?><label class="perm"><input type="checkbox" name="perms[]" value="<?= e($p) ?>" <?= in_array($p, $permsOf[(int)$r['id']] ?? [], true) ? 'checked' : '' ?>> <?= e($p) ?></label><?php endforeach; ?>
    </div>
    <div style="height:10px"></div>
    <button class="btn btn-ghost btn-sm" type="submit">Simpan permission</button>
  </form>
  <?php else: ?><p style="color:var(--mut);font-size:14px">Super admin memiliki akses penuh tanpa batas.</p><?php endif; ?>
</div>
<?php endforeach; ?>
