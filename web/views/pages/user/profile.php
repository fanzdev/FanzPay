<?php /** @var array $user */ /** @var int $balance */ /** @var string|null $error */ /** @var string|null $ok */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <div class="profile-top"><span class="avatar-xl"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span><div><h3><?= e($user['name']) ?></h3><small><?= e($user['email']) ?> · <?= e($user['phone']) ?></small></div></div>
  <div class="stat-mini">
    <div><small>Saldo</small><b><?= rupiah($balance) ?></b></div>
    <div><small>ID Publik</small><b class="mono" style="font-size:12.5px"><?= e($user['public_id'] ?? '—') ?></b></div>
    <div><small>Status</small><b>Aktif</b></div>
  </div>
  <form method="post" action="<?= url('profil') ?>" style="margin-top:14px">
    <?= \App\Core\CSRF::field() ?>
    <div class="field"><label for="name">Nama tampilan</label><input id="name" name="name" required maxlength="100" value="<?= e($user['name']) ?>"></div>
    <button class="btn btn-primary btn-block" type="submit">Simpan perubahan</button>
  </form>
</div>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('shieldCheck', 22) ?></span><div><h3>Keamanan &amp; akun</h3><p>Kelola akses dan identitasmu.</p></div></div>
  <div class="menu-list">
    <a href="<?= url('pin') ?>"><?= icon('lock', 19) ?>Kelola PIN transaksi<span class="go"><?= icon('chev', 17) ?></span></a>
    <a href="<?= url('qr') ?>"><?= icon('qr', 19) ?>QR Code saya<span class="go"><?= icon('chev', 17) ?></span></a>
    <a href="<?= url('notifikasi') ?>"><?= icon('bell', 19) ?>Notifikasi<span class="go"><?= icon('chev', 17) ?></span></a>
    <a href="<?= url('riwayat') ?>"><?= icon('receipt', 19) ?>Riwayat transaksi<span class="go"><?= icon('chev', 17) ?></span></a>
  </div>
  <div class="info-note"><?= icon('lock', 18) ?><span>Jangan bagikan <strong>PIN 6 digit</strong> ke siapa pun, termasuk yang mengaku admin.</span></div>
</div>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('wallet', 22) ?></span><div><h3>Dompet</h3><p>Saldo &amp; mutasi tercatat penuh.</p></div></div>
  <p>Saldo saat ini <b><?= rupiah($balance) ?></b></p>
  <p style="color:var(--mut);font-size:13px">Setiap mutasi tercatat di ledger dan bisa direkonstruksi penuh.</p>
</div>
