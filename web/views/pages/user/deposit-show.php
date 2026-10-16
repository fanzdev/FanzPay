<?php
/** @var array $d */ /** @var string $method_label */ /** @var string $method_kind */ /** @var bool $is_mock */
$st = (string)($d['status'] ?? 'pending');
$expTs = !empty($d['expired_at']) ? strtotime((string)$d['expired_at']) : 0;
$actions = [];
if (!empty($d['actions'])) { $tmp = json_decode((string)$d['actions'], true); if (is_array($tmp)) $actions = $tmp; }
$qrImg = (string)($d['qr_url'] ?? '');
if ($qrImg === '' && !empty($d['qr_string'])) $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode((string)$d['qr_string']);
$deeplink = (string)($d['payment_url'] ?? '');
foreach ($actions as $a) { if (($a['name'] ?? '') === 'deeplink-redirect' && !empty($a['url'])) $deeplink = (string)$a['url']; }
?>
<div class="panel pay-hero">
  <div class="pay-top">
    <div><small>Total dibayar</small><b><?= rupiah((int)$d['total_amount']) ?></b>
      <span class="pay-break">Deposit <?= rupiah((int)$d['amount']) ?> + biaya <?= rupiah((int)$d['fee']) ?> · saldo +<?= rupiah((int)$d['credit_amount']) ?></span>
    </div>
    <span id="payBadge"><?= status_badge($st) ?></span>
  </div>
  <div class="pay-meta mono">Ref <?= e($d['reference_id']) ?> · <?= e($method_label) ?></div>
  <?php if ($is_mock): ?><div class="alert ok" style="margin-top:10px">Mode sandbox — instruksi di bawah adalah simulasi. Tidak ada dana nyata bergerak.</div><?php endif; ?>
  <?php if ($st === 'pending' && $expTs > 0): ?>
    <div class="pay-exp">Berlaku hingga <b id="payCount" data-exp="<?= $expTs ?>">--:--</b></div>
  <?php endif; ?>
</div>

<?php if ($st === 'success'): ?>
  <div class="alert ok">Pembayaran terverifikasi. Saldo <?= rupiah((int)$d['credit_amount']) ?> telah masuk. <a href="<?= url('riwayat') ?>">Lihat riwayat</a>.</div>
<?php elseif ($st === 'expired'): ?>
  <div class="alert err">Pembayaran kedaluwarsa. Saldo tidak bertambah. Silakan <a href="<?= url('deposit') ?>">buat deposit baru</a>.</div>
<?php elseif ($st === 'failed'): ?>
  <div class="alert err">Pembayaran gagal. Saldo tidak bertambah. Silakan <a href="<?= url('deposit') ?>">coba lagi</a>.</div>
<?php endif; ?>

<?php if ($method_kind === 'qris' || ($method_kind === 'ewallet' && $qrImg !== '')): ?>
<div class="panel qr-box">
  <h3 style="margin-bottom:4px">Pindai kode QR</h3>
  <p style="color:var(--mut);font-size:13px">Buka e-wallet / m-banking apa pun, lalu pindai.</p>
  <?php if ($qrImg !== ''): ?><img src="<?= e($qrImg) ?>" alt="Kode QR pembayaran" width="250" height="250" style="border-radius:12px;background:#fff;padding:8px"><?php endif; ?>
  <?php if ($deeplink !== '' && $deeplink[0] !== '#'): ?><p><a class="btn btn-ghost" href="<?= e($deeplink) ?>" target="_blank" rel="noopener">Buka aplikasi pembayaran</a></p><?php endif; ?>
</div>
<?php elseif ($method_kind === 'va'): ?>
<div class="panel">
  <h3>Instruksi Virtual Account</h3>
  <p style="color:var(--mut);font-size:13px">Transfer tepat <b><?= rupiah((int)$d['total_amount']) ?></b> ke nomor berikut via ATM / m-banking / internet banking.</p>
  <div class="va-num"><span id="vaNum"><?= e((string)($d['payment_code'] ?? '-')) ?></span>
    <button type="button" class="btn btn-ghost btn-sm" data-copy="#vaNum">Salin</button></div>
  <div class="va-total"><span>Total transfer</span><b><?= rupiah((int)$d['total_amount']) ?></b>
    <button type="button" class="btn btn-ghost btn-sm" data-copy-text="<?= (int)$d['total_amount'] ?>">Salin nominal</button></div>
  <ol class="guide">
    <li><span class="g-num">1</span><span>Pilih menu transfer Virtual Account di bank <b><?= e($method_label) ?></b>.</span></li>
    <li><span class="g-num">2</span><span>Masukkan nomor di atas dengan nominal persis.</span></li>
    <li><span class="g-num">3</span><span>Tunggu status berubah otomatis di halaman ini.</span></li>
  </ol>
</div>
<?php else: ?>
<div class="panel">
  <h3>Instruksi e-wallet</h3>
  <?php if ($qrImg !== ''): ?><img src="<?= e($qrImg) ?>" alt="Kode QR e-wallet" width="230" height="230" style="border-radius:12px;background:#fff;padding:8px"><?php endif; ?>
  <?php if ($deeplink !== '' && $deeplink[0] !== '#'): ?><p><a class="btn btn-primary" href="<?= e($deeplink) ?>" target="_blank" rel="noopener">Bayar dengan <?= e($method_label) ?></a></p><?php endif; ?>
  <p style="color:var(--mut);font-size:13px">Selesaikan pembayaran sebesar <b><?= rupiah((int)$d['total_amount']) ?></b> sebelum kedaluwarsa.</p>
</div>
<?php endif; ?>

<div class="panel" data-dep-poll="<?= e($d['reference_id']) ?>" data-base="<?= url('') ?>">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <button type="button" class="btn btn-ghost" id="checkStatusBtn"><?= icon('refresh', 16) ?> Cek status</button>
    <a class="btn btn-ghost" href="<?= url('deposit') ?>"><?= icon('plus', 16) ?> Deposit lain</a>
    <a class="btn btn-ghost" href="<?= url('riwayat') ?>"><?= icon('clock', 16) ?> Riwayat</a>
  </div>
  <p style="color:var(--mut);font-size:13px;margin:10px 0 0">Status final ditentukan server &amp; hasil verifikasi resmi. Jangan tutup halaman ini sebelum lunas/kedaluwarsa.</p>
</div>

<style>
.pay-hero{background:var(--grad);color:#fff;border:none}
.pay-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.pay-top small{opacity:.85;font-size:13px;display:block}
.pay-top b{font-family:var(--disp);font-size:30px;font-weight:800}
.pay-break{display:block;font-size:12.5px;opacity:.85;margin-top:4px}
.pay-meta{font-size:12px;opacity:.85;margin-top:8px}
.pay-exp{margin-top:10px;background:rgba(255,255,255,.18);border-radius:12px;padding:8px 12px;font-size:14px}
.va-num{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;background:var(--bg-2);border:1px dashed var(--line);border-radius:12px;padding:14px;font-size:24px;font-weight:800;font-family:var(--disp);letter-spacing:2px;margin:10px 0}
.va-num span{min-width:0;overflow-wrap:anywhere}
.va-total{display:flex;align-items:center;justify-content:space-between;gap:10px;border-top:1px solid var(--line-2);padding-top:10px}
</style>
<script>
(function(){
function copyText(t){if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t);}else{var i=document.createElement('textarea');i.value=t;document.body.appendChild(i);i.select();try{document.execCommand('copy');}catch(e){}i.remove();}}
document.querySelectorAll('[data-copy]').forEach(function(b){b.addEventListener('click',function(){var el=document.querySelector(b.getAttribute('data-copy'));if(el)copyText(el.textContent.trim());b.textContent='Tersalin!';setTimeout(function(){b.textContent='Salin';},1500);});});
document.querySelectorAll('[data-copy-text]').forEach(function(b){b.addEventListener('click',function(){copyText(b.getAttribute('data-copy-text'));b.textContent='Tersalin!';setTimeout(function(){b.textContent='Salin nominal';},1500);});});
var exp=document.getElementById('payCount');
function tick(){if(!exp)return;var s=Math.max(0,exp.getAttribute('data-exp')-Math.floor(Date.now()/1000));var m=Math.floor(s/60),ss=('0'+(s%60)).slice(-2);exp.textContent=m+':'+ss;}
tick();setInterval(tick,1000);
var ref=document.querySelector('[data-dep-poll]');if(!ref)return;
var id=ref.getAttribute('data-dep-poll'),base=ref.getAttribute('data-base').replace(/\/$/,'');
function badge(st){var map={success:'<span class="badge b-ok">Berhasil</span>',pending:'<span class="badge b-warn">Menunggu</span>',expired:'<span class="badge b-mut">Kedaluwarsa</span>',failed:'<span class="badge b-bad">Gagal</span>'};return map[st]||st;}
function check(){
fetch(base+'/deposit/'+encodeURIComponent(id)+'/status',{headers:{'Accept':'application/json'},credentials:'same-origin'})
.then(function(r){return r.json();}).then(function(j){
if(!j||!j.ok||!j.data)return;
var b=document.getElementById('payBadge');if(b)b.innerHTML=badge(j.data.status);
if(j.data.status==='success'){location.reload();}
}).catch(function(){});
}
var btn=document.getElementById('checkStatusBtn');if(btn)btn.addEventListener('click',check);
setInterval(check,5000);
})();
</script>
