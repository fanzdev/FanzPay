<?php /** @var array $s */ /** @var string|null $ok */ ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Pengaturan sistem (super admin)</h3>
  <form method="post" action="<?= url('admin/pengaturan') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Nama situs</label><input name="site_name" value="<?= e($s['site_name'] ?? 'FanzPay') ?>"></div>
      <div class="field"><label>Mode provider</label><select name="provider_mode"><option value="sandbox" <?= ($s['provider_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>sandbox</option><option value="production" <?= ($s['provider_mode'] ?? '') === 'production' ? 'selected' : '' ?>>production</option><option value="manual" <?= ($s['provider_mode'] ?? '') === 'manual' ? 'selected' : '' ?>>manual</option></select></div>
    </div>
    <div class="grid2">
      <div class="field"><label>Min deposit</label><input name="min_deposit" type="number" value="<?= e($s['min_deposit'] ?? '10000') ?>"></div>
      <div class="field"><label>Max deposit</label><input name="max_deposit" type="number" value="<?= e($s['max_deposit'] ?? '5000000') ?>"></div>
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
