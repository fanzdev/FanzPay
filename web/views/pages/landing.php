<?php
/** Landing FanzPay — Gen-Z, filosofis, profesional, ANTI DATA DEMO. */
/** @var array $featured */ /** @var int $catalog_total */ /** @var int $cat_total */ /** @var array $apk */
?>
<section class="hero"><div class="wrap hero-grid">
  <div class="anim-fadeUp">
    <span class="eyebrow"><span class="pulse"></span> Dompet digital + PPOB · Live &amp; transparan</span>
    <h1>Uangmu, <span class="grad-text">ceritamu.</span><br>Kelola dengan tenang, gas dengan berani.</h1>
    <p class="lead">FanzPay itu <strong>dompet digital + PPOB</strong> buat generasi yang nggak mau ribet tapi tetap mikir: <strong>deposit QRIS real-time</strong>, <strong>transfer sesama Rp0</strong>, tarik dana ke bank &amp; e-wallet, beli pulsa, data, PLN, e-money, sampai voucher game. Semua tercatat di <strong>ledger yang bisa diaudit</strong> — tanpa biaya siluman, tanpa drama.</p>
    <div class="hero-cta">
      <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Buat akun gratis →</a>
      <a class="btn btn-apk btn-lg" href="<?= url('download-apk') ?>">⬇ Unduh APK Android<?= $apk['exists'] ? ' · <small>' . e($apk['size_human']) . '</small>' : '' ?></a>
    </div>
    <div class="tick"><?= icon('check', 17) ?> <span><strong>Satu pintu masuk:</strong> pelanggan, admin &amp; super admin login di halaman yang sama.</span></div>
    <div class="tick"><?= icon('check', 17) ?> <span><strong>Android khusus pelanggan.</strong> Admin &amp; Super Admin kelola semuanya dari web yang aman.</span></div>
    <div class="tick"><?= icon('check', 17) ?> <span><strong>PIN 6 digit</strong> untuk setiap aksi sensitif · Webhook terverifikasi · Anti saldo ganda.</span></div>
    <div class="hero-proof">
      <span><?= icon('zap', 16) ?> QRIS real-time</span>
      <span><?= icon('swap', 16) ?> Transfer Rp0 antar pengguna</span>
      <span><?= icon('shield', 16) ?> Ledger auditable</span>
    </div>
  </div>
  <div class="phone-wrap anim-pop" style="--d:.15s">
    <div class="phone-orb" aria-hidden="true"></div>
    <div class="phone" role="img" aria-label="Pratinjau aplikasi FanzPay">
      <div class="phone-top">
        <div class="phone-hello">Halo, Pejuang 👋<b>Saldo FanzPay kamu</b></div>
        <span class="badge b-ok">LIVE</span>
      </div>
      <div class="phone-bal">
        <div class="lbl">Total saldo aktif</div>
        <div class="amt">Rp ••••••</div>
        <div class="row"><span class="qa">Deposit</span><span class="qa">Transfer</span><span class="qa">Withdraw</span><span class="qa">Riwayat</span></div>
      </div>
      <?php if (!empty($featured)): ?>
        <?php foreach (array_slice($featured, 0, 3) as $f): ?>
          <div class="phone-tx">
            <span class="mini-ic"><?= icon('zap', 17) ?></span>
            <span style="flex:1;min-width:0"><b style="display:block;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($f['name']) ?></b><small class="muted"><?= e($f['cat_name'] ?? 'Digital') ?></small></span>
            <b style="font-size:13.5px"><?= rupiah((int)($f['price'] ?? 0)) ?></b>
          </div>
        <?php endforeach; ?>
        <p class="muted" style="font-size:12.5px;margin:10px 0 0">+ <?= number_format((int)$catalog_total) ?> produk live lainnya · diperbarui dari provider resmi.</p>
      <?php else: ?>
        <div class="phone-tx">
          <span class="mini-ic"><?= icon('shield', 17) ?></span>
          <span style="flex:1"><b style="display:block;font-size:13.5px">Katalog live dari provider resmi</b><small class="muted">Tanpa harga palsu. Harga final tampil setelah sinkron.</small></span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div></section>

<section class="sect" id="produk"><div class="wrap">
  <div class="reveal"><span class="eyebrow">Katalog live</span>
  <h2>Satu dompet untuk semua kebutuhan harianmu.</h2>
  <p class="sub">Yang kamu lihat di bawah ini <strong>data asli</strong> dari katalog aktif — bukan contoh. <?= (int)$catalog_total > 0 ? 'Saat ini ada <strong>' . number_format((int)$catalog_total) . ' produk</strong> dalam <strong>' . number_format((int)$cat_total) . ' kategori</strong>.' : 'Katalog sedang disiapkan dari provider resmi — harga final akan tampil otomatis setelah sinkron pertama.' ?></p></div>
  <?php if (!empty($featured)): ?>
  <div class="prod-grid">
    <?php foreach ($featured as $i => $f): ?>
      <div class="prod reveal" style="--d:<?= number_format($i * 0.06, 2) ?>s">
        <div><b><?= e($f['name']) ?></b><small><?= e($f['cat_name'] ?? '') ?> · harga final</small></div>
        <span><?= isset($f['price']) && (int)$f['price'] > 0 ? rupiah((int)$f['price']) : 'Lihat' ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="muted reveal" style="margin-top:14px;font-size:13.5px">Harga final, tanpa biaya tersembunyi. <a href="<?= url('register') ?>">Buat akun</a> untuk lihat semua <?= number_format((int)$catalog_total) ?> produk.</p>
  <?php else: ?>
  <div class="card reveal"><h3>Katalog sedang disiapkan 🌱</h3><p>Kami tidak menampilkan harga palsu. Begitu admin melakukan sinkronisasi DigiFlazz pertama, ratusan produk real (pulsa, data, PLN, game) akan muncul di sini otomatis.</p></div>
  <?php endif; ?>
</div></section>

<section class="sect" id="fitur"><div class="wrap">
  <div class="reveal"><span class="eyebrow">Kenapa FanzPay</span>
  <h2>Simpel di depan, <span class="grad-text">serius di belakang.</span></h2>
  <p class="sub">Vibes-nya santai. Sistemnya galak soal keamanan dan kejujuran data.</p></div>
  <div class="cards3">
    <div class="card reveal"><?= icon('swap') ?><h3>Transfer gratis, secepat chat</h3><p>Kirim saldo ke sesama pengguna via nomor HP atau scan QR. Atomic: gagal di satu sisi, batal semua. Rp0, tanpa drama.</p></div>
    <div class="card reveal" style="--d:.08s"><?= icon('qr') ?><h3>Deposit QRIS yang jujur</h3><p>QR dibuat di server. Status hanya dipercaya dari webhook terverifikasi + HMAC. Callback ganda tidak akan menggandakan saldo.</p></div>
    <div class="card reveal" style="--d:.16s"><?= icon('shield') ?><h3>Keamanan berlapis, anti panik</h3><p>PIN terpisah dari kata sandi, lockout otomatis, rate-limit, CSRF, audit log, dan ledger append-only yang bisa direkonsiliasi.</p></div>
    <div class="card reveal"><?= icon('zap') ?><h3>PPOB lengkap, harga final</h3><p>Pulsa, data, PLN, e-money, voucher game dari provider resmi. Yang tampil = yang kamu bayar. Margin diatur transparan oleh admin.</p></div>
    <div class="card reveal" style="--d:.08s"><?= icon('phone') ?><h3>Web + Android, sinkron mulus</h3><p>Pelanggan bebas: buka di browser atau aplikasi Android. Satu saldo, satu riwayat, satu PIN. Admin &amp; super admin fokus di web yang powerful.</p></div>
    <div class="card reveal" style="--d:.16s"><?= icon('chart') ?><h3>Command Center yang waras</h3><p>Admin lihat volume, pending, liabilitas, dan rekonsiliasi dalam satu dasbor. Super admin atur permission granular per admin.</p></div>
  </div>
</div></section>

<section class="sect" id="filosofi"><div class="wrap">
  <div class="philo reveal">
    <span class="eyebrow">Filosofi kami</span>
    <blockquote>“Pelan tapi pasti. Kami percaya uang bukan cuma angka — itu kepercayaan, mimpi, dan begadangmu. Makanya kami bangun FanzPay setransparan kaca: setiap rupiah tercatat, setiap proses bisa dijelaskan.”</blockquote>
    <p class="muted" style="margin:0">Buat Gen-Z yang hustle-nya kencang tapi tetap mindful: nggak perlu jadi sultan untuk dihargai. Mulai dari nominal kecil, bangun kebiasaan baik, biarin sistem yang kerja keras di belakang layar. <strong>#TransparanTanpaDrama</strong></p>
  </div>
</div></section>

<section class="sect" id="cara"><div class="wrap">
  <div class="reveal"><span class="eyebrow">Mulai 2 menit</span>
  <h2>Dari mager jadi degrader saldo. Eh, jadi sultan.</h2>
  <p class="sub">Daftar → verifikasi email → buat PIN → deposit pertama. Semudah pesan kopi.</p></div>
  <div class="cards3 steps">
    <div class="card reveal"><div class="step-num">1</div><h3>Daftar + verifikasi</h3><p>Nama, nomor HP, email, kata sandi. Kode OTP meluncur ke email. Super admin pertama? Langsung ke <a href="<?= url('setup-super') ?>">setup-super</a>.</p></div>
    <div class="card reveal" style="--d:.08s"><div class="step-num">2</div><h3>Deposit via QRIS</h3><p>Pilih nominal, scan QR, saldo masuk otomatis setelah pembayaran terverifikasi. Polling hemat, webhook jadi sumber kebenaran.</p></div>
    <div class="card reveal" style="--d:.16s"><div class="step-num">3</div><h3>Gas transaksi</h3><p>Beli produk, transfer gratis, atau tarik dana. Semua butuh PIN 6 digit. Semua tercatat rapi di riwayat.</p></div>
  </div>
</div></section>

<section class="sect"><div class="wrap">
  <div class="apk-band reveal">
    <div>
      <span class="eyebrow" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#fff">Android App · Gratis</span>
      <h2>Bawa FanzPay ke mana pun kamu pergi.</h2>
      <p>Aplikasi Android khusus pelanggan: cek saldo, beli produk, transfer via scan QR, deposit &amp; withdraw, semua dengan sentuhan glass yang mulus. Admin &amp; super admin tetap di web — lebih aman, lebih lengkap.</p>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;position:relative;z-index:1">
        <a class="btn btn-primary btn-lg" href="<?= url('download-apk') ?>">⬇ Unduh fanzpay.apk<?= $apk['exists'] ? ' · ' . e($apk['size_human']) : '' ?></a>
        <a class="btn btn-ghost btn-lg" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff" href="<?= url('register') ?>">Coba di web dulu</a>
      </div>
      <p style="font-size:12.5px;margin:12px 0 0;position:relative;z-index:1"><?= $apk['exists'] ? 'File asli dari server ini — versi terbaru, siap install. Aktifkan “Install unknown apps” sekali saja.' : 'Build resmi dari Android Studio. Setelah admin mengunggah fanzpay.apk ke server, tombol ini langsung mengunduh file asli.' ?></p>
    </div>
    <div class="phone-wrap">
      <div class="phone" style="animation:none">
        <div class="phone-top"><div class="phone-hello">FanzPay Android<b>Khusus pelanggan 💜</b></div><span class="badge b-ok">APK</span></div>
        <div class="phone-bal"><div class="lbl">Filosofi</div><div class="amt" style="font-size:22px">Pelan tapi pasti.</div><div class="row"><span class="qa">Pulsa</span><span class="qa">Data</span><span class="qa">PLN</span><span class="qa">Game</span></div></div>
        <div class="phone-tx"><span class="mini-ic"><?= icon('qr', 17) ?></span><span style="flex:1"><b style="display:block;font-size:13.5px">Scan QR antar teman</b><small class="muted">fanzpay://pay/u/…</small></span></div>
      </div>
    </div>
  </div>
</div></section>

<section class="sect faq"><div class="wrap" style="max-width:820px">
  <div class="reveal"><span class="eyebrow">FAQ jujur</span><h2>Yang sering ditanyain.</h2></div>
  <details class="reveal"><summary>Apakah ada biaya transfer antar pengguna?</summary><p class="muted">Nol. Rp0. Gratis. Transfer antar pengguna FanzPay tidak dipungut biaya apa pun.</p></details>
  <details class="reveal"><summary>Di mana saya daftar sebagai Super Admin?</summary><p class="muted">Di <a href="<?= url('setup-super') ?>">/setup-super</a>. Halaman ini hanya aktif saat belum ada super admin sama sekali. Setelah satu akun dibuat, halaman terkunci permanen demi keamanan.</p></details>
  <details class="reveal"><summary>Di mana saya login sebagai admin?</summary><p class="muted">Satu pintu: <a href="<?= url('login') ?>">/login</a> untuk semua peran. Sistem otomatis mengarahkanmu ke dasbor yang tepat. Aplikasi Android khusus pelanggan — admin wajib lewat web.</p></details>
  <details class="reveal"><summary>Apakah harga produk asli?</summary><p class="muted">Ya. Katalog ditarik dari provider resmi (DigiFlazz) dan harga final ditentukan admin secara transparan. Kami anti harga palsu — kalau katalog kosong, kami bilang kosong.</p></details>
  <details class="reveal"><summary>Bagaimana cara install APK?</summary><p class="muted">Unduh via tombol di atas → buka file → izinkan “Install unknown apps” → Install → login. Pastikan kamu mengunduh dari domain resmi ini.</p></details>
</div></section>

<section class="sect"><div class="wrap">
  <div class="card reveal" style="text-align:center;padding:40px 28px">
    <h2 style="font-size:clamp(24px,3.4vw,36px)">Siap nulis cerita uangmu sendiri?</h2>
    <p class="sub" style="margin-left:auto;margin-right:auto">Gabung sekarang. Gratis, 2 menit, tanpa kartu kredit. Masa depan finansialmu nggak nungguin.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Buat akun gratis</a>
      <a class="btn btn-ghost btn-lg" href="<?= url('download-apk') ?>">⬇ Unduh APK</a>
    </div>
  </div>
</div></section>
