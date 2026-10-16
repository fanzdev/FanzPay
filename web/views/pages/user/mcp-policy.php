<?php
/** @var array $user */ /** @var array $policy */ /** @var array $cats */ /** @var string|null $ok */ /** @var string|null $error */
$sys = $policy['system'] ?? ['max_per_tx' => 100000, 'max_daily' => 500000, 'allowed_categories' => []];
$myCats = $policy['allowed_categories'] ?? [];
?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <h3>Kebijakan pembelian otomatis via AI</h3>
  <p class="muted">Default <b>nonaktif</b>. AI hanya bisa membeli bila Anda mengaktifkan, dan tidak bisa melewati batas di bawah ini. Batas sistem admin: maks <?= rupiah((int)$sys['max_per_tx']) ?>/trx, <?= rupiah((int)$sys['max_daily']) ?>/hari.</p>
  <div class="alert err">Risiko: AI yang memegang token purchase bisa menghabiskan saldo sampai batas ini tanpa PIN per transaksi. Aktifkan hanya untuk AI yang Anda percaya, pasang batas kecil, dan cabut token bila aneh. PIN tidak pernah diminta AI — bila ada yang meminta PIN, itu penipuan.</div>
  <form method="post" action="<?= url('mcp-kebijakan') ?>">
    <?= \App\Core\CSRF::field() ?>
    <label class="perm"><input type="checkbox" name="enabled" value="1" <?= !empty($policy['enabled']) ? 'checked' : '' ?>> Aktifkan pembelian otomatis</label>
    <div class="grid2">
      <div class="field"><label>Maks per transaksi (Rp, ≤ <?= number_format((int)$sys['max_per_tx']) ?>)</label><input name="max_per_tx" type="number" min="0" max="<?= (int)$sys['max_per_tx'] ?>" value="<?= (int)($policy['max_per_tx'] ?? 0) ?>"></div>
      <div class="field"><label>Maks akumulasi harian (Rp, ≤ <?= number_format((int)$sys['max_daily']) ?>)</label><input name="max_daily" type="number" min="0" max="<?= (int)$sys['max_daily'] ?>" value="<?= (int)($policy['max_daily'] ?? 0) ?>"></div>
    </div>
    <div class="field"><label>Kategori yang diizinkan</label>
      <?php foreach (($cats ?: array_map(fn($c) => ['code' => $c, 'name' => $c], $sys['allowed_categories'])) as $c): ?>
        <label class="perm"><input type="checkbox" name="cats[]" value="<?= e($c['code']) ?>" <?= in_array($c['code'], $myCats, true) ? 'checked' : '' ?>> <?= e($c['name']) ?> <small>(<?= e($c['code']) ?>)</small></label>
      <?php endforeach; ?>
    </div>
    <label class="perm"><input type="checkbox" name="allow_new_numbers" value="1" <?= !empty($policy['allow_new_numbers']) ? 'checked' : '' ?>> Izinkan nomor tujuan baru (bila mati, AI hanya boleh beli ke nomor yang pernah Anda beli)</label>
    <label class="perm"><input type="checkbox" name="agree_risk" value="1"> Saya memahami risiko di atas dan menyetujui kebijakan ini</label>
    <div style="height:10px"></div>
    <button class="btn btn-primary" type="submit">Simpan kebijakan</button>
    <a class="btn btn-ghost" href="<?= url('mcp-koneksi') ?>">Kembali</a>
  </form>
</div>
