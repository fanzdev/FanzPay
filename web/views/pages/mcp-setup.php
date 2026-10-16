<?php
/** Tutorial AI Agentic (MCP) — referensi UX: Pluang Agentic Trading. @var string $endpoint */
$ep = $endpoint ?? url('mcp');
$tokUrl = url('mcp-koneksi');
$polUrl = url('mcp-kebijakan');
?>
<style>
.agx-hero{background:radial-gradient(1000px 480px at 18% -10%,rgba(0,64,240,.5) 0%,transparent 58%),linear-gradient(180deg,#0A1A33 0%,#0D1730 100%);color:#EDF3FF;border-radius:16px;padding:clamp(28px,5vw,64px);position:relative;overflow:hidden}
.agx-hero h1{color:#fff;font-size:clamp(28px,4.4vw,50px);line-height:1.08;margin:.4em 0;letter-spacing:-.02em}
.agx-hero h1 em{font-style:normal;background:linear-gradient(90deg,#7FB2FC,#B3D3FD);-webkit-background-clip:text;background-clip:text;color:transparent}
.agx-hero .lead{color:#B3D3FD;max-width:640px}
.agx-prompt{display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);border-radius:999px;padding:10px 18px;color:#EDF3FF;font-size:15px;margin-top:14px}
.agx-hero-cta{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
.agx-btn-light{background:#fff;color:#0A1A33;font-weight:700;border-radius:12px;padding:13px 26px;display:inline-flex;align-items:center;gap:8px}
.agx-risk{font-size:13px;color:#9DB8FF;margin-top:14px}
.agx-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:18px}
.agx-step{background:var(--card,#fff);border:1px solid var(--line,rgba(10,26,51,.1));border-radius:12px;padding:22px;position:relative}
.agx-num{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#0040F0,#1090F0);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.agx-urlbox{display:flex;align-items:center;gap:8px;background:var(--bg,#F0F4FA);border:1px dashed var(--line,rgba(10,26,51,.2));border-radius:10px;padding:10px 12px;margin-top:10px;font-size:13px;overflow:hidden}
.agx-urlbox code{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.agx-copy{border:1px solid var(--line,rgba(10,26,51,.2));border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;display:inline-flex;gap:6px;align-items:center;cursor:pointer;background:var(--card,#fff)}
.agx-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}
.agx-tab{border:1px solid var(--line,rgba(10,26,51,.15));background:var(--card,#fff);border-radius:999px;padding:8px 18px;font-weight:600;font-size:14px;cursor:pointer}
.agx-tab.on{background:#0A1A33;color:#fff;border-color:#0A1A33}
.agx-cap{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
.agx-chat{background:var(--card,#fff);border:1px solid var(--line,rgba(10,26,51,.1));border-radius:12px;padding:20px}
.agx-chat.wide{grid-column:1/-1}
.agx-q{background:linear-gradient(135deg,#0040F0,#1090F0);color:#fff;border-radius:12px 12px 4px 12px;padding:12px 16px;margin-bottom:12px;font-size:15px}
.agx-a{background:var(--bg,#F0F4FA);border-radius:4px 12px 12px 12px;padding:14px 16px;font-size:14.5px}
.agx-a table{width:100%;border-collapse:collapse;font-size:13.5px;margin-top:8px}
.agx-a th,.agx-a td{border-bottom:1px solid var(--line,rgba(10,26,51,.12));padding:7px 6px;text-align:left}
.agx-bars{display:flex;gap:10px;align-items:flex-end;margin-top:12px;height:110px}
.agx-bar{flex:1;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,#0040F0,#1090F0);position:relative;min-height:14px}
.agx-bar small{position:absolute;bottom:-22px;left:0;right:0;text-align:center;font-size:11px}
.agx-tl{margin:10px 0 0;padding:0;list-style:none}
.agx-tl li{display:flex;gap:10px;padding:9px 0;border-bottom:1px dashed var(--line,rgba(10,26,51,.12));font-size:14px}
.agx-tl li:last-child{border:0}
.agx-tick{color:#157F53;flex:none;margin-top:2px}
.agx-note{font-size:12.5px;color:var(--mut,#6B7C99);margin-top:10px}
.agx-cats{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
.agx-cat{border:1px solid var(--line,rgba(10,26,51,.15));border-radius:12px;padding:12px 16px;display:flex;gap:10px;align-items:center;background:var(--card,#fff);font-size:14px}
.agx-cat b{display:block}
.agx-cat small{color:var(--mut,#6B7C99)}
.agx-faq details{border:1px solid var(--line,rgba(10,26,51,.12));border-radius:12px;padding:14px 18px;margin-bottom:10px;background:var(--card,#fff)}
.agx-faq summary{font-weight:700;cursor:pointer}
.agx-code{background:#0A1A33;color:#EAF1FB;border-radius:12px;padding:14px 16px;overflow:auto;font-size:13px;margin-top:10px}
.agx-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:860px){.agx-steps,.agx-cap,.agx-grid2{grid-template-columns:1fr}}
</style>

<section class="sect"><div class="wrap">
  <div class="agx-hero">
    <span class="eyebrow eyebrow-light">Platform Agentic PPOB Indonesia</span>
    <h1>Analisis saldo, riset produk, dan beli PPOB dengan <em>FanzPay AI Agentic</em></h1>
    <p class="lead">Dibangun di atas MCP — protokol standar yang memungkinkan AI terhubung ke akun FanzPay-mu. Hubungkan AI yang sudah kamu pakai, tanpa coding, tanpa API key.</p>
    <div><span class="agx-prompt"><?= icon('spark', 17) ?> “Berapa pengeluaran pulsa saya bulan ini?”</span></div>
    <div class="agx-hero-cta">
      <a class="agx-btn-light" href="#mcp-steps">Hubungkan FanzPay MCP <?= icon('arrowR', 17) ?></a>
      <a class="btn btn-ghost btn-lg cta-login" href="<?= $tokUrl ?>">Saya sudah punya token</a>
    </div>
    <p class="agx-risk">Asisten AI memiliki risiko finansial. Setiap pembelian membutuhkan kebijakan yang kamu setujui dan tidak pernah meminta PIN-mu.</p>
  </div>
</div></section>

<section class="sect" id="mcp-steps"><div class="wrap" style="max-width:960px">
  <div class="sect-head"><span class="eyebrow">Mulai dalam 3 langkah</span>
    <h2>Hubungkan FanzPay MCP dalam tiga langkah.</h2>
    <p class="sub">Kredensialmu diproses oleh FanzPay, bukan penyedia AI. Model AI dibayar dari akun AI-mu sendiri.</p>
  </div>
  <div class="agx-tabs" role="tablist" aria-label="Pilih AI">
    <button class="agx-tab on" data-agx="claude" role="tab">Claude</button>
    <button class="agx-tab" data-agx="chatgpt" role="tab">ChatGPT</button>
    <button class="agx-tab" data-agx="cursor" role="tab">Cursor</button>
    <button class="agx-tab" data-agx="other" role="tab">AI Lainnya</button>
  </div>
  <div class="agx-steps">
    <div class="agx-step"><span class="agx-num">1</span><b>Salin URL MCP FanzPay</b>
      <p class="muted" style="font-size:14px">Satu URL untuk semua AI yang kompatibel dengan MCP.</p>
      <div class="agx-urlbox"><code id="agx-url"><?= e($ep) ?></code><button class="agx-copy" data-copy="#agx-url"><?= icon('copy', 15) ?> Salin</button></div>
    </div>
    <div class="agx-step"><span class="agx-num">2</span><b>Tambah konektor kustom</b>
      <p class="muted" style="font-size:14px" id="agx-step2">Di Claude, buka Settings → Connectors → Add custom connector. Beri nama FanzPay, tempel URL di atas, pilih auth Bearer.</p>
    </div>
    <div class="agx-step"><span class="agx-num">3</span><b>Login &amp; buat token</b>
      <p class="muted" style="font-size:14px"><a href="<?= url('login') ?>">Masuk</a> ke FanzPay → buka <a href="<?= $tokUrl ?>">Koneksi AI</a> → buat token → tempel sebagai Bearer token. Analisis aktif seketika; pembelian tetap nonaktif hingga kamu mengatur batasnya di <a href="<?= $polUrl ?>">Kebijakan AI</a>.</p>
    </div>
  </div>
</div></section>

<section class="sect"><div class="wrap" style="max-width:960px">
  <div class="sect-head"><span class="eyebrow">Bisa apa saja</span>
    <h2>Atur sekali, lihat hasilnya.</h2>
    <p class="sub">Setiap contoh dimulai dari satu pesan ke asistenmu. Analisis bisa dipakai sejak hari pertama; pembelian hanya berjalan atas instruksi dan batas yang kamu tentukan.</p>
  </div>
  <div class="agx-cap">
    <div class="agx-chat">
      <b><?= icon('chart', 18) ?> Analis pengeluaran</b>
      <p class="muted" style="font-size:13.5px">Tanya apa pun dari data transaksimu yang asli.</p>
      <div class="agx-q">“Bulan ini saya paling banyak keluar untuk apa?”</div>
      <div class="agx-a">Bulan berjalan, <b>PPOB Rp 312rb</b> mendominasi (68%), disusul deposit masuk Rp 500rb:
        <div class="agx-bars"><div class="agx-bar" style="height:88%"><small>Pulsa</small></div><div class="agx-bar" style="height:64%"><small>Data</small></div><div class="agx-bar" style="height:40%"><small>PLN</small></div><div class="agx-bar" style="height:24%"><small>Game</small></div></div>
        <div style="height:26px"></div>Mau saya ingatkan sebelum kuota data habis?
      </div>
      <p class="agx-note">Hanya ilustrasi. Analisis alat bantu, bukan saran finansial.</p>
    </div>
    <div class="agx-chat">
      <b><?= icon('search', 18) ?> Riset produk</b>
      <p class="muted" style="font-size:13.5px">Bandingkan harga jual aktual dari katalog FanzPay.</p>
      <div class="agx-q">“Bandingkan pulsa 25rb semua operator.”</div>
      <div class="agx-a"><table><tr><th>Produk</th><th>Harga</th></tr><tr><td>Pulsa TS 25rb</td><td>Rp 27.500</td></tr><tr><td>Pulsa XL 25rb</td><td>Rp 27.000</td></tr><tr><td>Pulsa Indosat 25rb</td><td>Rp 27.250</td></tr></table>Singkatnya: <b>XL termurah saat ini.</b> Mau disiapkan pembeliannya?</div>
      <p class="agx-note">Hanya ilustrasi. Harga mengikuti katalog saat transaksi.</p>
    </div>
    <div class="agx-chat wide">
      <b><?= icon('shieldCheck', 18) ?> Eksekusi terkonfirmasi</b>
      <p class="muted" style="font-size:13.5px">Setiap pembelian selalu dalam kendalimu — disiapkan dulu, dieksekusi setelah lolos batas.</p>
      <div class="agx-q">“Belikan pulsa 25rb ke 0812xxxx.”</div>
      <div class="agx-a"><ul class="agx-tl">
        <li><span class="agx-tick"><?= icon('checkCircle', 16) ?></span><span><b>Persiapan dibuat</b> — Pulsa XL 25rb · Rp 27.000 · berlaku 10 menit</span></li>
        <li><span class="agx-tick"><?= icon('checkCircle', 16) ?></span><span><b>Batas dicek</b> — di bawah maks Rp 100rb/transaksi dan saldo cukup</span></li>
        <li><span class="agx-tick"><?= icon('checkCircle', 16) ?></span><span><b>Dieksekusi</b> — status success, notifikasi dikirim ke FanzPay-mu</span></li>
      </ul></div>
      <p class="agx-note">Hanya ilustrasi. Batas aktual mengikuti pengaturanmu.</p>
    </div>
    <div class="agx-chat">
      <b><?= icon('bell', 18) ?> Ringkasan &amp; pengingat</b>
      <p class="muted" style="font-size:13.5px">Jadwalkan jawaban sesuai waktumu.</p>
      <div class="agx-q">“Ingatkan kalau token listrik mau habis.”</div>
      <div class="agx-a">Siap. Saya pantau riwayat token PLN-mu dan mengingatkan sebelum rata-rata pemakaianmu habis. AI tidak mengirim notifikasi sendiri — pengingat dikelola di FanzPay.</div>
      <p class="agx-note">Hanya ilustrasi.</p>
    </div>
    <div class="agx-chat">
      <b><?= icon('lock', 18) ?> Batasan keamanan</b>
      <p class="muted" style="font-size:13.5px">Agen yang tahu batasnya — ditegakkan server, di luar jangkauan AI.</p>
      <div class="agx-q">“Apa saja batasan AI saya?”</div>
      <div class="agx-a"><b>Tidak melewati batas yang ditentukan</b> — berlaku bahkan saat AI salah paham.<br><b>Tidak eksekusi tanpa kebijakan aktif</b> — default nonaktif.<br><b>Bisa diputus kapan saja</b> — cabut token dari Koneksi AI.</div>
      <p class="agx-note">Lihat &amp; ubah di <a href="<?= $polUrl ?>">Kebijakan AI</a>.</p>
    </div>
  </div>
</div></section>

<section class="sect"><div class="wrap" style="max-width:960px">
  <div class="sect-head"><span class="eyebrow">Cakupan</span><h2>Dari pulsa sampai voucher game.</h2></div>
  <div class="agx-cats">
    <div class="agx-cat"><?= icon('smartphone', 20) ?><span><b>Pulsa</b><small>Semua operator</small></span></div>
    <div class="agx-cat"><?= icon('wifi', 20) ?><span><b>Paket Data</b><small>Harian–bulanan</small></span></div>
    <div class="agx-cat"><?= icon('zap', 20) ?><span><b>PLN</b><small>Token &amp; tagihan</small></span></div>
    <div class="agx-cat"><?= icon('shieldCheck', 20) ?><span><b>BPJS</b><small>Kesehatan &amp; TK</small></span></div>
    <div class="agx-cat"><?= icon('wallet', 20) ?><span><b>E-Wallet</b><small>Top up favoritmu</small></span></div>
    <div class="agx-cat"><?= icon('gamepad', 20) ?><span><b>Voucher Game</b><small>Publisher resmi</small></span></div>
  </div>
</div></section>

<section class="sect"><div class="wrap agx-faq" style="max-width:860px">
  <div class="sect-head" style="text-align:center"><span class="eyebrow">FAQ</span><h2>Yang sering ditanyakan.</h2></div>
  <details open><summary>Bisakah dipakai hanya untuk analisis?</summary><p class="muted"><b>Ya.</b> Token default hanya scope baca: AI bisa cek saldo, riwayat, dan riset produk. Pembelian tetap nonaktif hingga kamu mengaktifkan kebijakan dan membuat token purchase.</p></details>
  <details><summary>AI apa saja yang didukung?</summary><p class="muted">Semua klien yang kompatibel dengan MCP: Claude, ChatGPT, Cursor, dan konektor generik. Kamu hanya butuh URL di atas + token dari halaman Koneksi AI — tanpa coding.</p></details>
  <details><summary>Bisakah AI membeli tanpa seizinku?</summary><p class="muted"><b>Tidak.</b> AI menyiapkan usulan pembelian; eksekusi hanya jalan bila kebijakanmu aktif dan semua batas lolos. Kamu bisa menolak, dan mencabut token kapan saja.</p></details>
  <details><summary>Bagaimana jika AI salah paham?</summary><p class="muted">Risiko terbesar adalah usulan pembelian yang salah — yang tetap terhalang batas server dan bisa kamu abaikan. Batasmu diterapkan independen di server FanzPay.</p></details>
  <details><summary>Bagaimana data saya ditangani penyedia AI?</summary><p class="muted">Hanya data yang diperlukan untuk menjawab permintaanmu yang dikirim ke AI pilihanmu. Putus koneksi kapan saja untuk menghentikan aliran data baru (cabut token di Koneksi AI).</p></details>
  <details><summary>Berapa biayanya?</summary><p class="muted">Tanpa biaya tambahan dari FanzPay. Harga produk standar tetap berlaku per transaksi. Kamu memerlukan akun pada AI pilihanmu (model AI dibayar dari akunmu sendiri).</p></details>
  <details><summary>Saya developer — di mana detail teknisnya?</summary><div>
    <p class="muted">Endpoint JSON-RPC 2.0 (Streamable HTTP): <code><?= e($ep) ?></code> · protokol <code>2024-11-05</code> · 18 tools · auth <code>Authorization: Bearer &lt;token&gt;</code>.</p>
    <div class="agx-code">curl -s <?= e($ep) ?> -H "Content-Type: application/json" -H "Authorization: Bearer TOKEN" -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'</div>
    <div class="agx-code">{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"prepare_ppob_purchase","arguments":{"product_code":"PULSA_XL25","customer_no":"0812xxxx"}}}</div>
  </div></details>
</div></section>

<section class="sect"><div class="wrap" style="max-width:960px">
  <div class="cta-band">
    <div class="cta-tx">
      <span class="eyebrow eyebrow-light">Mulai dalam hitungan menit</span>
      <h2>Siap menghubungkan AI ke FanzPay?</h2>
      <p>Buat token, tempel ke AI favoritmu, dan biarkan ia menganalisis — pembelian hanya jalan atas batas yang kamu tentukan.</p>
      <div class="cta-btns">
        <a class="btn btn-primary btn-lg" href="<?= $tokUrl ?>">Buat Token Sekarang <?= icon('arrowR', 18) ?></a>
        <a class="btn btn-ghost btn-lg cta-login" href="<?= $polUrl ?>">Atur Batas AI</a>
      </div>
    </div>
  </div>
</div></section>

<script>
(function(){
  var steps={
    claude:'Di Claude, buka Settings → Connectors → Add custom connector. Beri nama FanzPay, tempel URL di atas, pilih auth Bearer.',
    chatgpt:'Di ChatGPT, buka Settings → Connectors/Actions (atau GPTs → Configure → Actions). Tambah koneksi ke URL di atas dengan auth Bearer, lalu tempel tokenmu.',
    cursor:'Di Cursor, buka Settings → MCP → Add custom MCP server. Tipe Streamable HTTP, URL di atas, header Authorization: Bearer TOKEN.',
    other:'Di AI/klien lain (n8n, Make, Python, curl): POST HTTPS ke URL di atas dengan header Authorization: Bearer TOKEN memakai body JSON-RPC 2.0 — lihat contoh di FAQ teknis.'
  };
  document.querySelectorAll('[data-agx]').forEach(function(b){
    b.addEventListener('click',function(){
      document.querySelectorAll('[data-agx]').forEach(function(x){x.classList.remove('on')});
      b.classList.add('on');
      var el=document.getElementById('agx-step2');
      if(el&&steps[b.getAttribute('data-agx')])el.textContent=steps[b.getAttribute('data-agx')];
    });
  });
  document.querySelectorAll('[data-copy]').forEach(function(b){
    b.addEventListener('click',function(){
      var t=document.querySelector(b.getAttribute('data-copy'));
      if(!t)return;
      var v=t.innerText;
      if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(v).then(function(){b.innerHTML='Tersalin!'});}
      else{var r=document.createRange();r.selectNodeContents(t);var s=getSelection();s.removeAllRanges();s.addRange(r);try{document.execCommand('copy');b.innerHTML='Tersalin!';}catch(e){}s.removeAllRanges();}
    });
  });
})();
</script>
