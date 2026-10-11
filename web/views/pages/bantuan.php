<?php /** Pusat Bantuan — panduan mandiri + kontak + tentang perusahaan. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Pusat Bantuan</span>
  <h1>Butuh bantuan? <span class="grad-text">Mulai dari sini.</span></h1>
  <p class="lead">Panduan mandiri untuk akun, saldo, dan transaksi. Landing page ini untuk informasi — transaksi dan pengelolaan akun dilakukan di dashboard setelah masuk.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="<?= url('faq') ?>">Lihat FAQ <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('login') ?>">Masuk ke Dashboard</a>
  </div>
</div></section>

<section class="sect"><div class="wrap">
  <div class="cards3">
    <div class="card reveal"><?= icon('user') ?><h3>Akun dan Verifikasi</h3><p>Daftar dengan data valid, verifikasi OTP email, buat PIN 6 digit, dan jaga kerahasiaan kata sandi serta PIN.</p></div>
    <div class="card reveal" style="--d:.07s"><?= icon('qr') ?><h3>Deposit QRIS</h3><p>Buat QR dari menu Deposit, bayar sebelum kedaluwarsa, dan pantau status pending, berhasil, atau kedaluwarsa di Riwayat.</p></div>
    <div class="card reveal" style="--d:.14s"><?= icon('swap') ?><h3>Transfer dan QR</h3><p>Kirim saldo via nomor HP atau pindai QR pengguna. Gratis Rp0. Periksa nama penerima sebelum konfirmasi PIN.</p></div>
    <div class="card reveal"><?= icon('zap') ?><h3>Pembelian PPOB</h3><p>Pilih produk, masukkan nomor tujuan dengan teliti, periksa harga final, bayar dengan PIN, simpan nomor referensi.</p></div>
    <div class="card reveal" style="--d:.07s"><?= icon('bank') ?><h3>Penarikan Saldo</h3><p>Simpan tujuan bank atau e-wallet, ajukan penarikan, pantau status pending, disetujui, atau ditolak di Riwayat.</p></div>
    <div class="card reveal" style="--d:.14s"><?= icon('refresh') ?><h3>Kendala dan Refund</h3><p>Transaksi gagal otomatis kembali sebagai refund. Catat nomor referensi sebelum menghubungi customer service.</p></div>
  </div>

  <div class="help-contact reveal" id="kontak">
    <span class="layanan-ic"><?= icon('headset', 24) ?></span>
    <div>
      <h2>Hubungi Customer Service</h2>
      <p>Siapkan email akun, nomor HP terdaftar, nomor referensi transaksi (contoh: DEP, TRF, PPOB, WD), dan tangkapan layar status bila ada. Kanal kontak resmi tersedia di dalam dashboard setelah masuk, agar identitasmu terverifikasi.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <a class="btn btn-primary btn-sm" href="<?= url('login') ?>">Buka Dashboard <?= icon('arrowR', 15) ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= url('faq') ?>">Cek FAQ dulu</a>
      </div>
    </div>
  </div>

  <article class="card reveal" id="tentang" style="margin-top:18px">
    <h3>Tentang FanzPay</h3>
    <p>FanzPay adalah platform dompet digital dan PPOB yang mengutamakan transparansi harga, riwayat transaksi lengkap, dan keamanan akun berlapis (verifikasi email, PIN 6 digit, dan pencatatan audit). Website ini berfungsi untuk pemasaran dan informasi; seluruh transaksi dan pengelolaan akun dilakukan melalui dashboard pengguna yang aman.</p>
  </article>
</div></section>
