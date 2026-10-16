<?php
/** @var array $s */ /** @var array $masked */ /** @var string|null $ok */ /** @var string|null $error */
/** @var string $midtrans_mode */ /** @var bool $midtrans_ready */ /** @var string $webhook_url */
?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="border-left:4px solid <?= $midtrans_ready ? 'var(--mint)' : 'var(--amber)' ?>">
  <div class="dash-head"><span class="dash-ic"><?= icon('zap', 22) ?></span><div>
    <h3>Midtrans Core API — <?= $midtrans_ready ? 'terhubung' : 'belum dikonfigurasi' ?></h3>
    <p>Mode <b class="mono"><?= e($midtrans_mode) ?></b> · Server Key tidak pernah ditampilkan penuh &amp; tidak pernah ke browser.</p>
  </div></div>
  <form method="post" action="<?= url($panel . '/api') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Mode</label><select name="midtrans_mode"><option value="sandbox" <?= $midtrans_mode === 'sandbox' ? 'selected' : '' ?>>sandbox</option><option value="production" <?= $midtrans_mode === 'production' ? 'selected' : '' ?>>production</option></select></div>
      <div class="field"><label>Client Key (boleh tampil di frontend bila dibutuhkan)</label><input name="midtrans_client_key" type="password" autocomplete="off" placeholder="•••••••• (kosongkan = tidak berubah)"><small style="color:var(--mut)">Tersimpan: <?= e($masked['midtrans_client_key'] ?? '—') ?></small></div>
    </div>
    <div class="field"><label>Server Key (RAHASIA — hanya backend)</label><input name="midtrans_server_key" type="password" autocomplete="off" placeholder="•••••••• (kosongkan = tidak berubah)"><small style="color:var(--mut)">Tersimpan: <?= e($masked['midtrans_server_key'] ?? '—') ?> · prioritas .env bila terisi</small></div>
    <div class="field"><label>Notification URL (daftarkan di dashboard Midtrans)</label><input value="<?= e($webhook_url) ?>" readonly onclick="this.select()"></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-primary" type="submit">Simpan konfigurasi</button>
    </div>
  </form>
  <form method="post" action="<?= url($panel . '/api/midtrans-test') ?>" style="margin-top:10px">
    <?= \App\Core\CSRF::field() ?>
    <button class="btn btn-ghost" type="submit"><?= icon('refresh', 16) ?> Uji konektivitas (tanpa dana)</button>
  </form>
</div>

<div class="panel">
  <h3>DigiFlazz</h3>
  <form method="post" action="<?= url($panel . '/api') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Username</label><input name="digiflazz_user" value="<?= e($s['digiflazz_user'] ?? '') ?>" placeholder="diambil dari .env bila kosong"></div>
      <div class="field"><label>API key (tersimpan: <?= e($masked['digiflazz_key']) ?>)</label><input name="digiflazz_key" type="password" placeholder="•••••••• (kosongkan = tidak berubah)"></div>
    </div>
    <h3 style="margin-top:18px">BuatQris (legacy)</h3>
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
