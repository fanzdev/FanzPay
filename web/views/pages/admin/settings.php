<?php
/** @var array $s */ /** @var string|null $ok */ /** @var string|null $error */
$methods = array_keys(\App\Services\MidtransService::METHODS);
$active = array_filter(array_map('trim', explode(',', strtolower((string)($s['deposit_methods'] ?? 'qris,bca,bri,mandiri,permata,bni,gopay,shopeepay')))));
?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <h3>Pengaturan sistem (super admin)</h3>
  <form method="post" action="<?= url('admin/pengaturan') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Nama situs</label><input name="site_name" value="<?= e($s['site_name'] ?? 'FanzPay') ?>"></div>
      <div class="field"><label>Mode provider (PPOB)</label><select name="provider_mode"><option value="sandbox" <?= ($s['provider_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>sandbox</option><option value="production" <?= ($s['provider_mode'] ?? '') === 'production' ? 'selected' : '' ?>>production</option><option value="manual" <?= ($s['provider_mode'] ?? '') === 'manual' ? 'selected' : '' ?>>manual</option></select></div>
    </div>
    <h3 style="margin-top:18px">Kebijakan deposit (Midtrans)</h3>
    <label class="perm"><input type="checkbox" name="deposit_enabled" value="1" <?= ($s['deposit_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Fitur deposit aktif</label>
    <div class="grid2">
      <div class="field"><label>Gateway deposit</label><select name="deposit_gateway"><option value="midtrans" <?= ($s['deposit_gateway'] ?? 'midtrans') === 'midtrans' ? 'selected' : '' ?>>midtrans (QRIS/VA/e-wallet)</option><option value="buatqris" <?= ($s['deposit_gateway'] ?? '') === 'buatqris' ? 'selected' : '' ?>>buatqris (legacy QRIS)</option></select></div>
      <div class="field"><label>Mode Midtrans</label><select name="midtrans_mode"><option value="sandbox" <?= ($s['midtrans_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>sandbox</option><option value="production" <?= ($s['midtrans_mode'] ?? '') === 'production' ? 'selected' : '' ?>>production</option></select></div>
      <div class="field"><label>Min deposit (≥ Rp 10.000)</label><input name="min_deposit" type="number" min="10000" value="<?= e($s['min_deposit'] ?? '10000') ?>"></div>
      <div class="field"><label>Max deposit</label><input name="max_deposit" type="number" min="10000" value="<?= e($s['max_deposit'] ?? '5000000') ?>"></div>
      <div class="field"><label>Masa berlaku (menit, 5–60)</label><input name="deposit_expiry_minutes" type="number" min="5" max="60" value="<?= e($s['deposit_expiry_minutes'] ?? '15') ?>"></div>
      <div class="field"><label>Metode aktif (koma)</label><input name="deposit_methods" value="<?= e(implode(',', $active)) ?>"><small style="color:var(--mut)">Pilihan: <?= e(implode(', ', $methods)) ?></small></div>
    </div>
    <h3 style="margin-top:18px">Biaya transaksi (ditanggung pelanggan)</h3>
    <div class="grid2">
      <div class="field"><label>QRIS (%)</label><input name="midtrans_fee_qris_pct" type="number" step="0.01" min="0" value="<?= e($s['midtrans_fee_qris_pct'] ?? '0.7') ?>"></div>
      <div class="field"><label>GoPay (%)</label><input name="midtrans_fee_gopay_pct" type="number" step="0.01" min="0" value="<?= e($s['midtrans_fee_gopay_pct'] ?? '2') ?>"></div>
      <div class="field"><label>ShopeePay (%)</label><input name="midtrans_fee_shopeepay_pct" type="number" step="0.01" min="0" value="<?= e($s['midtrans_fee_shopeepay_pct'] ?? '2') ?>"></div>
      <div class="field"><label>VA BCA (Rp)</label><input name="midtrans_fee_bca" type="number" min="0" value="<?= e($s['midtrans_fee_bca'] ?? '4000') ?>"></div>
      <div class="field"><label>VA BRI (Rp)</label><input name="midtrans_fee_bri" type="number" min="0" value="<?= e($s['midtrans_fee_bri'] ?? '4000') ?>"></div>
      <div class="field"><label>VA BNI (Rp)</label><input name="midtrans_fee_bni" type="number" min="0" value="<?= e($s['midtrans_fee_bni'] ?? '4000') ?>"></div>
      <div class="field"><label>Mandiri (Rp)</label><input name="midtrans_fee_mandiri" type="number" min="0" value="<?= e($s['midtrans_fee_mandiri'] ?? '4000') ?>"></div>
      <div class="field"><label>VA Permata (Rp)</label><input name="midtrans_fee_permata" type="number" min="0" value="<?= e($s['midtrans_fee_permata'] ?? '4000') ?>"></div>
    </div>
    <h3 style="margin-top:18px">Batas lain</h3>
    <div class="grid2">
      <div class="field"><label>Min withdraw</label><input name="min_withdraw" type="number" value="<?= e($s['min_withdraw'] ?? '20000') ?>"></div>
      <div class="field"><label>Max withdraw</label><input name="max_withdraw" type="number" value="<?= e($s['max_withdraw'] ?? '5000000') ?>"></div>
      <div class="field"><label>Min transfer</label><input name="min_transfer" type="number" value="<?= e($s['min_transfer'] ?? '1000') ?>"></div>
      <div class="field"><label>Max transfer</label><input name="max_transfer" type="number" value="<?= e($s['max_transfer'] ?? '10000000') ?>"></div>
    </div>
    <label class="perm"><input type="checkbox" name="maintenance_mode" value="1" <?= ($s['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>> Maintenance mode</label>
    <div style="height:12px"></div>
    <button class="btn btn-primary" type="submit">Simpan</button>
  </form>
</div>
