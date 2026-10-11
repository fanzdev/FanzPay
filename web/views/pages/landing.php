<?php
/**
 * Landing FanzPay — struktur profesional 10 bagian.
 * 1 Navbar (layout) · 2 Hero · 3 Trust · 4 Layanan · 5 Cara Kerja
 * 6 Keunggulan · 7 Harga · 8 FAQ · 9 CTA · 10 Footer (layout)
 * Hanya ikon SVG via icon(). Tanpa emoji. Tanpa data demo.
 */
/** @var array $featured */ /** @var int $catalog_total */ /** @var int $cat_total */ /** @var array $apk */
/** @var int $min_deposit */ /** @var int $max_deposit */ /** @var int $min_withdraw */ /** @var int $min_transfer */

$services = [
  ['id' => 'pulsa',  'icon' => 'smartphone', 'name' => 'Pulsa',                 'desc' => 'Isi ulang pulsa semua operator langsung dari saldo FanzPay.'],
  ['id' => 'data',   'icon' => 'wifi',       'name' => 'Paket Data',            'desc' => 'Paket internet harian, mingguan, dan bulanan semua operator.'],
  ['id' => 'pln',    'icon' => 'zap',        'name' => 'Token Listrik PLN',     'desc' => 'Beli token prabayar PLN dengan nomor meter ID tujuan.'],
  ['id' => 'pln-tag','icon' => 'receipt',    'name' => 'Tagihan Listrik',       'desc' => 'Bayar tagihan listrik pascabayar PLN tepat waktu.'],
  ['id' => 'bpjs',   'icon' => 'shieldCheck','name' => 'BPJS',                  'desc' => 'Bayar iuran BPJS Kesehatan dan Ketenagakerjaan.'],
  ['id' => 'emoney', 'icon' => 'wallet',     'name' => 'Top Up E-Wallet',       'desc' => 'Isi saldo e-money dan dompet digital favoritmu.'],
  ['id' => 'game',   'icon' => 'gamepad',    'name' => 'Voucher Game',          'desc' => 'Voucher dan item game dari publisher resmi.'],
  ['id' => 'digital','icon' => 'grid',       'name' => 'Produk Digital Lainnya','desc' => 'TV dan internet, PDAM, serta produk digital lain sesuai katalog.'],
];
?>

<!-- 2. HERO -->
<section class="hero hero-elegant"><div class="wrap hero-grid">
  <div class="anim-fadeUp">
    <span class="eyebrow"><span class="pulse"></span> Dompet Digital dan PPOB Indonesia</span>
    <h1>Satu aplikasi untuk semua kebutuhan <span class="grad-text">digital dan tagihanmu.</span></h1>
    <p class="lead">FanzPay adalah <strong>dompet digital dan layanan PPOB</strong>: isi saldo via QRIS real-time, transfer gratis antar pengguna, tarik dana ke bank dan e-wallet, lalu beli pulsa, paket data, token dan tagihan PLN, BPJS, top up e-wallet, hingga voucher game. Harga transparan, riwayat lengkap, dan setiap transaksi tercatat rapi.</p>
    <div class="hero-cta">
      <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Mulai Sekarang <?= icon('arrowR', 18) ?></a>
      <a class="btn btn-ghost btn-lg" href="<?= url('layanan') ?>">Lihat Layanan</a>
    </div>
    <div class="hero-mini">
      <div class="hero-mini-item"><?= icon('checkCircle', 18) ?><span>Registrasi gratis, verifikasi email</span></div>
      <div class="hero-mini-item"><?= icon('checkCircle', 18) ?><span>PIN 6 digit untuk setiap transaksi sensitif</span></div>
      <div class="hero-mini-item"><?= icon('checkCircle', 18) ?><span>Web responsif dan aplikasi Android untuk pelanggan</span></div>
    </div>
  </div>
  <div class="ambassador-wrap anim-pop" style="--d:.15s">
    <div class="ambassador-orb" aria-hidden="true"></div>
    <figure class="ambassador-card">
      <img src="<?= asset('img/ambassador.webp') ?>" alt="Brand ambassador FanzPay" width="688" height="1024" fetchpriority="high">
      <figcaption class="ambassador-cap">
        <span class="badge b-ok">Layanan Aktif</span>
        <span>Deposit QRIS · Transfer Rp0 · PPOB lengkap</span>
      </figcaption>
    </figure>
    <div class="amb-float amb-float-1"><?= icon('qr', 18) ?><span>Transfer via QR Code</span></div>
    <div class="amb-float amb-float-2"><?= icon('shieldCheck', 18) ?><span>Ledger teraudit</span></div>
  </div>
</div></section>

<!-- 3. TRUST -->
<section class="sect trust-sect"><div class="wrap">
  <div class="reveal trust-head">
    <span class="eyebrow">Kenapa dipercaya</span>
    <h2>Transaksi yang mudah, harga yang jujur.</h2>
    <p class="sub">Lima fondasi yang membuat FanzPay aman dipakai harian — dari nominal kecil sampai kebutuhan keluarga.</p>
  </div>
  <div class="trust-grid">
    <div class="trust-item reveal"><?= icon('zap', 20) ?><div><b>Kemudahan Transaksi</b><small>Alur beli dan bayar yang singkat, status jelas setiap langkah.</small></div></div>
    <div class="trust-item reveal" style="--d:.06s"><?= icon('tag', 20) ?><div><b>Transparansi Harga</b><small>Harga final tampil di awal. Tanpa biaya tersembunyi.</small></div></div>
    <div class="trust-item reveal" style="--d:.12s"><?= icon('receipt', 20) ?><div><b>Riwayat Transaksi</b><small>Semua aktivitas tersimpan lengkap dan mudah ditelusuri.</small></div></div>
    <div class="trust-item reveal" style="--d:.18s"><?= icon('lock', 20) ?><div><b>Keamanan Akun</b><small>PIN terpisah dari kata sandi, verifikasi email, dan audit log.</small></div></div>
    <div class="trust-item reveal" style="--d:.24s"><?= icon('headset', 20) ?><div><b>Bantuan Pelanggan</b><small>Pusat bantuan dan panduan mandiri yang selalu tersedia.</small></div></div>
  </div>
</div></section>

<!-- 4. LAYANAN PPOB -->
<section class="sect" id="layanan"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Layanan PPOB</span>
    <h2>Delapan layanan dalam satu saldo.</h2>
    <p class="sub">Semua layanan di bawah ini tersedia melalui dashboard setelah masuk.<?php if ((int)$catalog_total > 0): ?> Saat ini tersedia <strong><?= number_format((int)$catalog_total) ?> produk</strong> dalam <strong><?= number_format((int)$cat_total) ?> kategori</strong> aktif.<?php else: ?> Katalog produk dimuat langsung dari provider resmi — harga final tampil setelah sinkronisasi.<?php endif; ?></p>
  </div>
  <div class="layanan-grid">
    <?php foreach ($services as $i => $s): ?>
      <a class="layanan-card reveal" style="--d:<?= number_format($i * 0.05, 2) ?>s" href="<?= url('layanan') ?>#<?= e($s['id']) ?>">
        <span class="layanan-ic"><?= icon($s['icon'], 22) ?></span>
        <span class="layanan-tx"><b><?= e($s['name']) ?></b><small><?= e($s['desc']) ?></small></span>
        <span class="layanan-go"><?= icon('arrowR', 18) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($featured)): ?>
  <div class="prod-live reveal">
    <div class="prod-live-head"><b>Contoh produk aktif</b><a href="<?= url('layanan') ?>">Lihat semua layanan <?= icon('arrowR', 15) ?></a></div>
    <div class="prod-grid">
      <?php foreach (array_slice($featured, 0, 6) as $f): ?>
        <div class="prod">
          <div><b><?= e($f['name']) ?></b><small><?= e($f['cat_name'] ?? '') ?> · harga final</small></div>
          <span><?= isset($f['price']) && (int)$f['price'] > 0 ? rupiah((int)$f['price']) : 'Lihat' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div></section>

<!-- 5. CARA KERJA -->
<section class="sect steps-sect" id="cara-kerja"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Cara Kerja</span>
    <h2>Lima langkah dari daftar sampai transaksi berhasil.</h2>
    <p class="sub">Transaksi dilakukan di dashboard setelah masuk. Alurnya sama di web dan aplikasi Android.</p>
  </div>
  <ol class="steps-timeline">
    <li class="reveal"><span class="step-dot">1</span><div><b>Registrasi akun</b><p>Buat akun gratis dengan nama, nomor HP, email, dan kata sandi, lalu verifikasi email.</p></div></li>
    <li class="reveal" style="--d:.06s"><span class="step-dot">2</span><div><b>Pilih produk atau layanan</b><p>Masuk ke dashboard, buka menu produk, dan pilih layanan yang dibutuhkan.</p></div></li>
    <li class="reveal" style="--d:.12s"><span class="step-dot">3</span><div><b>Masukkan nomor tujuan</b><p>Isi nomor HP, ID meter PLN, atau nomor pelanggan tujuan dengan teliti.</p></div></li>
    <li class="reveal" style="--d:.18s"><span class="step-dot">4</span><div><b>Lakukan pembayaran</b><p>Konfirmasi dengan PIN 6 digit. Saldo terpotong sesuai harga final yang tampil.</p></div></li>
    <li class="reveal" style="--d:.24s"><span class="step-dot">5</span><div><b>Periksa status transaksi</b><p>Pantau status di menu Riwayat dan terima notifikasi otomatis setiap perubahan.</p></div></li>
  </ol>
  <div class="reveal steps-cta">
    <a class="btn btn-primary" href="<?= url('register') ?>">Daftar Sekarang <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('login') ?>">Sudah punya akun? Masuk</a>
  </div>
</div></section>

<!-- 6. KEUNGGULAN -->
<section class="sect" id="keunggulan"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Keunggulan FanzPay</span>
    <h2>Satu platform, delapan keunggulan nyata.</h2>
    <p class="sub">Semua fitur berikut tersedia dan aktif — bukan rencana, bukan ilustrasi.</p>
  </div>
  <div class="unggul-grid">
    <div class="card reveal"><?= icon('grid') ?><h3>Satu platform multi-kebutuhan</h3><p>Dompet, PPOB, transfer, dan penarikan dalam satu akun dan satu saldo.</p></div>
    <div class="card reveal" style="--d:.05s"><?= icon('tag') ?><h3>Harga jual transparan</h3><p>Harga final ditampilkan sebelum bayar. Margin diatur terbuka oleh admin.</p></div>
    <div class="card reveal" style="--d:.1s"><?= icon('receipt') ?><h3>Riwayat transaksi lengkap</h3><p>Setiap deposit, transfer, PPOB, dan penarikan tercatat dengan referensi unik.</p></div>
    <div class="card reveal" style="--d:.15s"><?= icon('bell') ?><h3>Notifikasi transaksi</h3><p>Pemberitahuan otomatis untuk status berhasil, pending, gagal, dan refund.</p></div>
    <div class="card reveal"><?= icon('wallet') ?><h3>Pengelolaan saldo</h3><p>Deposit QRIS real-time, cek saldo, dan kelola dana dari dashboard.</p></div>
    <div class="card reveal" style="--d:.05s"><?= icon('swap') ?><h3>Transfer antar pengguna</h3><p>Kirim saldo ke sesama pengguna via nomor HP. Gratis, tanpa biaya.</p></div>
    <div class="card reveal" style="--d:.1s"><?= icon('qr') ?><h3>Transfer via QR Code</h3><p>Pindai QR pengguna lain untuk transfer cepat tanpa salah ketik.</p></div>
    <div class="card reveal" style="--d:.15s"><?= icon('smartphone') ?><h3>Responsif di HP dan desktop</h3><p>Tampilan web menyesuaikan layar, plus aplikasi Android khusus pelanggan.</p></div>
  </div>
</div></section>

<!-- 7. HARGA DAN BIAYA -->
<section class="sect harga-sect" id="harga"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Harga dan Biaya Transaksi</span>
    <h2>Biaya yang jelas sebelum kamu bayar.</h2>
    <p class="sub">Ringkasan kebijakan biaya FanzPay. Detail lengkap ada di halaman <a href="<?= url('harga') ?>">Harga dan Biaya</a>.</p>
  </div>
  <div class="harga-grid">
    <div class="card reveal"><span class="harga-ic"><?= icon('tag', 20) ?></span><h3>Harga produk</h3><p>Harga jual tampil final di katalog dan halaman konfirmasi sebelum pembayaran.</p></div>
    <div class="card reveal" style="--d:.05s"><span class="harga-ic"><?= icon('receipt', 20) ?></span><h3>Biaya administrasi</h3><p>Jika ada biaya admin provider, nominalnya ditampilkan rinci — tidak disembunyikan.</p></div>
    <div class="card reveal" style="--d:.1s"><span class="harga-ic"><?= icon('qr', 20) ?></span><h3>Ketentuan deposit</h3><p>Deposit via QRIS, minimal <?= rupiah((int)($min_deposit ?? 10000)) ?> dan maksimal <?= rupiah((int)($max_deposit ?? 5000000)) ?> per transaksi.</p></div>
    <div class="card reveal"><span class="harga-ic"><?= icon('bank', 20) ?></span><h3>Penarikan saldo</h3><p>Tarik dana ke bank dan e-wallet yang tersimpan, minimal <?= rupiah((int)($min_withdraw ?? 20000)) ?>. Status dapat dipantau di Riwayat.</p></div>
    <div class="card reveal" style="--d:.05s"><span class="harga-ic"><?= icon('refresh', 20) ?></span><h3>Pengembalian dana</h3><p>Transaksi gagal otomatis dikembalikan ke saldo dan tercatat sebagai refund.</p></div>
    <div class="card reveal" style="--d:.1s"><span class="harga-ic"><?= icon('file', 20) ?></span><h3>Transaksi gagal</h3><p>Status gagal, kedaluwarsa, atau ditolak dijelaskan di Riwayat beserta tindak lanjutnya.</p></div>
  </div>
  <div class="reveal harga-note">
    <?= icon('swap', 18) ?>
    <span><strong>Transfer antar pengguna gratis (Rp0).</strong> Tidak ada potongan apa pun dari pengirim maupun penerima.</span>
    <a href="<?= url('harga') ?>">Lihat rincian <?= icon('arrowR', 15) ?></a>
  </div>
</div></section>

<!-- 8. FAQ -->
<section class="sect faq" id="faq"><div class="wrap" style="max-width:860px">
  <div class="reveal sect-head" style="text-align:center">
    <span class="eyebrow">FAQ</span>
    <h2>Sembilan jawaban paling dicari.</h2>
    <p class="sub" style="margin-left:auto;margin-right:auto">Versi lengkap dan panduan langkah demi langkah ada di halaman <a href="<?= url('faq') ?>">FAQ</a> dan <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>.</p>
  </div>
  <details class="reveal" open><summary>Apa itu FanzPay?</summary><p class="muted">FanzPay adalah dompet digital dan layanan PPOB: satu akun untuk menyimpan saldo, transfer antar pengguna, tarik dana, dan membeli produk digital seperti pulsa, paket data, listrik PLN, BPJS, e-money, dan voucher game.</p></details>
  <details class="reveal"><summary>Bagaimana cara mendaftar?</summary><p class="muted">Buka halaman Registrasi, isi nama, nomor HP, email, dan kata sandi, lalu verifikasi kode OTP yang dikirim ke email. Setelah itu buat PIN 6 digit untuk transaksi.</p></details>
  <details class="reveal"><summary>Bagaimana cara mengisi saldo?</summary><p class="muted">Masuk ke dashboard, buka menu Deposit, pilih nominal minimal <?= rupiah((int)($min_deposit ?? 10000)) ?>, lalu pindai QRIS yang muncul. Saldo masuk otomatis setelah pembayaran terverifikasi.</p></details>
  <details class="reveal"><summary>Bagaimana cara melakukan transaksi?</summary><p class="muted">Pilih produk di dashboard, masukkan nomor tujuan, periksa harga final, konfirmasi dengan PIN, lalu pantau statusnya di menu Riwayat.</p></details>
  <details class="reveal"><summary>Apa yang terjadi jika transaksi gagal?</summary><p class="muted">Dana otomatis dikembalikan ke saldo dan status berubah menjadi refund atau gagal. Kamu menerima notifikasi dan bisa melihat rinciannya di Riwayat.</p></details>
  <details class="reveal"><summary>Berapa lama proses transaksi?</summary><p class="muted">Sebagian besar transaksi PPOB diproses dalam hitungan detik hingga menit mengikuti respons provider. Deposit QRIS terverifikasi otomatis; status pending masih bisa dipantau di Riwayat dan Deposit.</p></details>
  <details class="reveal"><summary>Bagaimana cara transfer saldo?</summary><p class="muted">Buka menu Transfer, masukkan nomor HP atau pindai QR Code penerima, isi nominal minimal <?= rupiah((int)($min_transfer ?? 1000)) ?>, konfirmasi dengan PIN. Gratis, tanpa biaya.</p></details>
  <details class="reveal"><summary>Bagaimana cara mengamankan akun?</summary><p class="muted">Gunakan kata sandi yang kuat, jangan bagikan PIN 6 digit ke siapa pun, verifikasi email aktif, dan keluar dari perangkat bersama setelah memakai dashboard.</p></details>
  <details class="reveal"><summary>Bagaimana cara menghubungi customer service?</summary><p class="muted">Kunjungi halaman Pusat Bantuan untuk panduan mandiri dan kanal kontak resmi yang tersedia setelah masuk ke akun.</p></details>
</div></section>

<!-- 9. CTA -->
<section class="sect cta-sect"><div class="wrap">
  <div class="cta-band reveal">
    <div class="cta-tx">
      <span class="eyebrow eyebrow-light">Mulai dalam hitungan menit</span>
      <h2>Siap memakai FanzPay untuk kebutuhan harianmu?</h2>
      <p>Daftar gratis, verifikasi email, isi saldo pertama via QRIS, dan nikmati semua layanan dalam satu dashboard.</p>
      <div class="cta-btns">
        <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Daftar Sekarang <?= icon('arrowR', 18) ?></a>
        <a class="btn btn-ghost btn-lg cta-login" href="<?= url('login') ?>">Masuk ke Akun</a>
      </div>
    </div>
    <div class="cta-visual">
      <div class="cta-photo-ring">
        <img src="<?= asset('img/ambassador.webp') ?>" alt="Brand ambassador FanzPay" width="688" height="1024" loading="lazy">
      </div>
      <div class="cta-chip cta-chip-1"><?= icon('qr', 17) ?><span>Deposit QRIS real-time</span></div>
      <div class="cta-chip cta-chip-2"><?= icon('swap', 17) ?><span>Transfer antar pengguna Rp0</span></div>
      <div class="cta-chip cta-chip-3"><?= icon('shieldCheck', 17) ?><span>8 layanan dalam 1 saldo</span></div>
    </div>
  </div>
</div></section>
