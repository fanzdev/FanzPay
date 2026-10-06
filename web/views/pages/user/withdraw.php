<?php /** @var array $dests */ /** @var string|null $error */ /** @var string|null $ok */ /** @var int $min */ ?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<div class="panel">
  <h3>Tarik dana</h3>
  <form method="post" action="<?= url('withdraw') ?>" data-idem>
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="destination_id">Tujuan</label>
      <select id="destination_id" name="destination_id" required>
        <option value="">— pilih —</option>
        <?php foreach ($dests as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['label'] . ' • ' . $d['bank_name'] . ' ' . $d['bank_account']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label for="amount">Nominal (min <?= rupiah($min) ?>)</label><input id="amount" name="amount" type="number" min="<?= $min ?>" required></div>
    <div class="field"><label for="pin">PIN transaksi</label><input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required placeholder="••••••"></div>
    <button class="btn btn-primary btn-block" type="submit">Ajukan penarikan</button>
  </form>
</div>
<div class="panel">
  <h3>Tambah tujuan baru</h3>
  <form method="post" action="<?= url('withdraw/tujuan') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Label</label><input name="label" required maxlength="80" placeholder="BCA Pribadi"></div>
      <div class="field"><label>Bank / e-wallet</label><input name="bank_name" required maxlength="80" placeholder="Bank BCA"></div>
    </div>
    <div class="grid2">
      <div class="field"><label>Nomor rekening</label><input name="bank_account" required maxlength="40"></div>
      <div class="field"><label>Nama pemilik</label><input name="bank_holder" required maxlength="100"></div>
    </div>
    <button class="btn btn-ghost btn-block" type="submit">Simpan tujuan</button>
  </form>
</div>
