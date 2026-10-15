<?php
/** @var string|null $error */ /** @var string|null $ok */
/** @var int $min */ /** @var int $max */
/** @var array $methods */ /** @var int $exp_min */
/** @var bool $deposit_enabled */ /** @var array $pending */
/** @var int $balance */
use App\Services\MidtransService;
?>
<?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>

<div class="panel" style="background:var(--grad);color:#fff;border:none;overflow:hidden;position:relative">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
    <div>
      <div style="opacity:.85;font-size:13px">Saldo tersedia</div>
      <div style="font-family:var(--disp);font-size:30px;font-weight:800"><?= rupiah((int)($balance ?? 0)) ?></div>
      <div style="opacity:.8;font-size:12.5px">Deposit masuk otomatis setelah pembayaran terverifikasi · maks. <?= (int)$exp_min ?> menit</div>
    </div>
    <span style="background:rgba(255,255,255,.2);border-radius:14px;padding:10px"><?= icon('wallet', 26) ?></span>
  </div>
</div>

<?php if (!$deposit_enabled): ?>
  <div class="alert err">Fitur deposit sedang nonaktif. Silakan coba lagi nanti.</div>
<?php else: ?>
<div class="panel">
  <div class="dash-head"><span class="dash-ic"><?= icon('plus', 22) ?></span><div><h3>Deposit saldo</h3><p>Min. <?= rupiah($min) ?> · maks. <?= rupiah($max) ?> · biaya transparan sebelum bayar.</p></div></div>
  <form method="post" action="<?= url('deposit') ?>" data-idem id="depForm">
    <?= \App\Core\CSRF::field() ?>
    <input type="hidden" name="_idem" value="">
    <div class="field"><label for="amount">Nominal deposit (masuk ke saldo)</label><input id="amount" name="amount" type="number" min="<?= $min ?>" max="<?= $max ?>" step="1000" required placeholder="50000" inputmode="numeric"></div>
    <div class="grid2" style="grid-template-columns:repeat(4,1fr)">
      <?php foreach ([10000, 50000, 100000, 250000] as $q): ?>
        <button type="button" class="btn btn-ghost btn-sm" data-amt="<?= $q ?>"><?= rupiah($q) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="field" style="margin-top:14px"><label>Pilih metode pembayaran</label>
      <div class="dep-methods">
      <?php $i = 0; foreach ($methods as $code => $m): $i++; ?>
        <label class="dep-method<?= $i === 1 ? ' on' : '' ?>">
          <input type="radio" name="method" value="<?= e($code) ?>"<?= $i === 1 ? ' checked' : '' ?>>
          <span class="dep-m-ic"><?= icon($m['icon'], 20) ?></span>
          <span class="dep-m-tx"><b><?= e($m['label']) ?></b><small data-fee-note="<?= e($code) ?>"></small></span>
          <span class="dep-m-check"><?= icon('check', 16) ?></span>
        </label>
      <?php endforeach; ?>
      </div>
    </div>
    <div class="dep-summary" aria-live="polite">
      <div><span>Nominal deposit</span><b id="sumAmt">Rp 0</b></div>
      <div><span>Biaya transaksi</span><b id="sumFee">Rp 0</b></div>
      <div class="dep-total"><span>Total dibayar</span><b id="sumTot">Rp 0</b></div>
      <small>Biaya ditanggung pelanggan dan diteruskan ke penyedia pembayaran. Saldo bertambah sebesar nominal deposit.</small>
    </div>
    <div style="height:12px"></div>
    <button class="btn btn-primary btn-block" type="submit" id="depSubmit">Buat pembayaran</button>
  </form>
  <ol class="guide">
    <li><span class="g-num">1</span><span><b>Isi nominal &amp; pilih metode</b> — total dihitung otomatis.</span></li>
    <li><span class="g-num">2</span><span><b>Selesaikan pembayaran</b> sebelum kedaluwarsa (<?= (int)$exp_min ?> menit).</span></li>
    <li><span class="g-num">3</span><span><b>Saldo masuk otomatis</b> — pantau status di halaman bayar &amp; riwayat.</span></li>
  </ol>
</div>
<?php endif; ?>

<?php if (!empty($pending)): ?>
<div class="panel">
  <h3>Menunggu pembayaran</h3>
  <?php foreach ($pending as $p): ?>
    <div class="tx-row">
      <span class="tx-main"><b class="mono"><?= e($p['reference_id']) ?></b><small><?= e(MidtransService::METHODS[$p['payment_type']]['label'] ?? $p['payment_type']) ?> · <?= rupiah((int)$p['total_amount']) ?> · kedaluwarsa <?= e($p['expired_at'] ?? '-') ?></small></span>
      <a class="btn btn-ghost btn-sm" href="<?= url('deposit/' . $p['reference_id']) ?>">Lanjutkan</a>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.dep-methods{display:grid;gap:10px;grid-template-columns:1fr 1fr}
@media(max-width:560px){.dep-methods{grid-template-columns:1fr}}
.dep-method{display:flex;align-items:center;gap:10px;border:1.5px solid var(--line);border-radius:14px;padding:11px 12px;cursor:pointer;background:var(--card-solid);transition:border-color .2s,box-shadow .2s;position:relative}
.dep-method input{position:absolute;opacity:0;pointer-events:none}
.dep-method.on{border-color:var(--brand-600);box-shadow:0 0 0 3px rgba(27,77,255,.12)}
.dep-m-ic{display:inline-flex;width:38px;height:38px;border-radius:11px;background:var(--grad-soft);align-items:center;justify-content:center;color:var(--brand-600)}
.dep-m-tx{display:flex;flex-direction:column;line-height:1.3}
.dep-m-tx small{color:var(--mut);font-size:12px}
.dep-m-check{margin-left:auto;color:var(--brand-600);opacity:0}
.dep-method.on .dep-m-check{opacity:1}
.dep-summary{margin-top:14px;border:1px dashed var(--line);border-radius:14px;padding:12px 14px;background:color-mix(in srgb,var(--card-solid) 70%,transparent)}
.dep-summary>div{display:flex;justify-content:space-between;padding:3px 0;font-size:14px}
.dep-summary .dep-total{border-top:1px solid var(--line);margin-top:6px;padding-top:8px;font-size:16px}
.dep-summary .dep-total b{font-family:var(--disp)}
.dep-summary small{color:var(--mut);font-size:12px}
</style>
<script>
(function(){
var amt=document.getElementById('amount');if(!amt)return;
var cfg={};
<?php foreach ($methods as $code => $m): $pctKey = $code==='qris'?'midtrans_fee_qris_pct':($code==='gopay'?'midtrans_fee_gopay_pct':($code==='shopeepay'?'midtrans_fee_shopeepay_pct':null)); ?>
cfg['<?= e($code) ?>']={flat:<?= $pctKey===null ? (int)\App\Services\Settings::get('midtrans_fee_'.$code,'4000') : 0 ?>,pct:<?= $pctKey===null ? '-1' : json_encode((float)\App\Services\Settings::get($pctKey,'0')) ?>};
<?php endforeach; ?>
function cur(){return document.querySelector('input[name=method]:checked');}
function feeFor(code,a){var c=cfg[code];if(!c)return 0;if(c.pct>0&&c.pct!==-1)return Math.ceil(a*c.pct/100);return c.flat;}
function fmt(n){return 'Rp '+Number(n||0).toLocaleString('id-ID');}
function upd(){
var a=Math.max(0,parseInt(amt.value||'0',10));
var m=cur()?cur().value:'qris';
var f=feeFor(m,a);
document.getElementById('sumAmt').textContent=fmt(a);
document.getElementById('sumFee').textContent=fmt(f);
document.getElementById('sumTot').textContent=fmt(a+f);
document.querySelectorAll('[data-fee-note]').forEach(function(el){
var cd=el.getAttribute('data-fee-note');var c2=cfg[cd];
el.textContent=(c2.pct>0&&c2.pct!==-1)?('Biaya ±'+c2.pct+'%'):('Biaya '+fmt(c2.flat));
});
}
document.querySelectorAll('[data-amt]').forEach(function(b){b.addEventListener('click',function(){amt.value=b.getAttribute('data-amt');upd();});});
document.querySelectorAll('input[name=method]').forEach(function(r){r.addEventListener('change',function(){
document.querySelectorAll('.dep-method').forEach(function(l){l.classList.remove('on');});
r.closest('.dep-method').classList.add('on');upd();
});});
amt.addEventListener('input',upd);upd();
})();
</script>
