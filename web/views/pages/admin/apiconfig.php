<?php /** @var array $s */ /** @var array $masked */ /** @var string|null $ok */ ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>DigiFlazz</h3>
  <form method="post" action="<?= url('admin/api') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Username</label><input name="digiflazz_user" value="<?= e($s['digiflazz_user'] ?? '') ?>" placeholder="diambil dari .env bila kosong"></div>
      <div class="field"><label>API key (tersimpan: <?= e($masked['digiflazz_key']) ?>)</label><input name="digiflazz_key" type="password" placeholder="•••••••• (kosongkan = tidak berubah)"></div>
    </div>
    <h3 style="margin-top:18px">BuatQris</h3>
    <div class="grid2">
      <div class="field"><label>Account ID</label><input name="buatqris_account" value="<?= e($s['buatqris_account'] ?? '') ?>"></div>
      <div class="field"><label>Secret token (tersimpan: <?= e($masked['buatqris_secret']) ?>)</label><input name="buatqris_secret" type="password" placeholder="••••••••"></div>
      <div class="field"><label>Webhook signing secret (tersimpan: <?= e($masked['buatqris_sign']) ?>)</label><input name="buatqris_sign" type="password" placeholder="••••••••"></div>
      <div class="field"><label>WD account ID (tujuan penarikan terdaftar)</label><input name="buatqris_wd_account" value="<?= e($s['buatqris_wd_account'] ?? '') ?>"></div>
    </div>
    <p style="color:var(--mut);font-size:13px">Kredensial hanya dipakai server-side, tidak pernah dikirim ke browser. Nilai .env tetap prioritas bila terisi.</p>
    <button class="btn btn-primary" type="submit">Simpan konfigurasi</button>
  </form>
</div>
