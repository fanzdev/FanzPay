<?php /** @var array $rows */ /** @var array $permsOf */ /** @var array $perms */ ?>
<?php if ($err = flash('error')): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
<?php if ($ok = flash('ok')): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <div class="admin-head">
    <span class="auth-medal"><?= icon('users', 24) ?></span>
    <div>
      <h3>Buat admin baru</h3>
      <p>Admin mengelola operasional dari web dengan permission granular. <?= count($rows) ?> akun terdaftar.</p>
    </div>
  </div>
  <form method="post" action="<?= url($panel . '/admin/buat') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Nama lengkap</label><input name="name" required maxlength="100" autocomplete="off" placeholder="Nama admin"></div>
      <div class="field"><label>Email kerja</label><input name="email" type="email" required autocomplete="off" placeholder="admin@domain.com"></div>
    </div>
    <div class="field"><label>Kata sandi awal</label>
      <div class="pass-wrap"><input id="new-admin-pass" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter"><button type="button" class="pass-eye" data-peek="#new-admin-pass" aria-label="Tampilkan kata sandi"></button></div>
      <div class="hint">Minta admin bersangkutan mengganti kata sandi setelah login pertama.</div>
    </div>
    <div class="field"><label>Permission awal</label><div class="perm-grid">
      <?php foreach ($perms as $p): ?><label class="perm"><input type="checkbox" name="perms[]" value="<?= e($p) ?>"> <?= e($p) ?></label><?php endforeach; ?>
    </div></div>
    <button class="btn btn-primary" type="submit"><?= icon('plus', 17) ?> Buat Admin</button>
  </form>
</div>
<?php foreach ($rows as $r): ?>
<div class="panel">
  <div class="admin-card-head">
    <span class="avatar-init"><?= e(mb_substr($r['name'] ?? '?', 0, 1)) ?></span>
    <div>
      <h3><?= e($r['name']) ?></h3>
      <small><?= e($r['email']) ?></small>
    </div>
    <span class="badge <?= $r['role'] === 'super_admin' ? 'b-info' : 'b-mut' ?>"><?= $r['role'] === 'super_admin' ? 'Super Admin' : 'Admin' ?></span>
  </div>
  <?php if ($r['role'] === 'admin'): ?>
  <form method="post" action="<?= url($panel . '/admin/' . $r['id'] . '/perms') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="perm-grid">
      <?php foreach ($perms as $p): ?><label class="perm"><input type="checkbox" name="perms[]" value="<?= e($p) ?>" <?= in_array($p, $permsOf[(int)$r['id']] ?? [], true) ? 'checked' : '' ?>> <?= e($p) ?></label><?php endforeach; ?>
    </div>
    <div style="height:12px"></div>
    <button class="btn btn-ghost btn-sm" type="submit"><?= icon('check', 15) ?> Simpan Permission</button>
  </form>
  <?php else: ?>
    <div class="secure-box" style="margin:0"><?= icon('shieldCheck', 19) ?><span>Super admin memiliki akses penuh ke seluruh modul tanpa batas permission.</span></div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
