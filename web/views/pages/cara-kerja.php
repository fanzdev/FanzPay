<?php /** Halaman Cara Kerja — 5 langkah transaksi FanzPay. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Cara Kerja</span>
  <h1>Lima langkah dari <span class="grad-text">daftar sampai berhasil.</span></h1>
  <p class="lead">Transaksi dilakukan di dashboard setelah masuk — alurnya sama di web dan aplikasi Android.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="<?= url('register') ?>">Daftar Sekarang <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('layanan') ?>">Lihat Layanan</a>
  </div>
</div></section>

<section class="sect"><div class="wrap">
  <ol class="steps-timeline steps-page">
    <li class="reveal"><span class="step-dot">1</span><div><b>Registrasi akun</b><p>Isi nama, nomor HP aktif, email aktif, dan kata sandi di halaman Registrasi, lalu verifikasi kode OTP yang dikirim ke email.</p><p class="muted" style="margin-top:8px">Hasil: akun aktif dan siap dibuatkan PIN transaksi.</p></div></li>
    <li class="reveal" style="--d:.06s"><span class="step-dot">2</span><div><b>Pilih produk atau layanan</b><p>Masuk ke dashboard, buka menu Produk, pilih kategori (pulsa, data, PLN, BPJS, e-money, game), lalu pilih nominal atau produk.</p><p class="muted" style="margin-top:8px">Hasil: harga final tampil sebelum kamu membayar.</p></div></li>
    <li class="reveal" style="--d:.12s"><span class="step-dot">3</span><div><b>Masukkan nomor tujuan</b><p>Isi nomor HP, ID meter PLN, atau nomor pelanggan dengan teliti. Periksa ulang — kesalahan input menjadi tanggung jawab pengguna.</p><p class="muted" style="margin-top:8px">Hasil: data tujuan terkunci di halaman konfirmasi.</p></div></li>
    <li class="reveal" style="--d:.18s"><span class="step-dot">4</span><div><b>Lakukan pembayaran</b><p>Konfirmasi dengan PIN 6 digit. Saldo terpotong persis sebesar harga final. Pastikan saldo cukup — isi dulu via Deposit QRIS bila kurang.</p><p class="muted" style="margin-top:8px">Hasil: transaksi diteruskan ke provider resmi.</p></div></li>
    <li class="reveal" style="--d:.24s"><span class="step-dot">5</span><div><b>Periksa status transaksi</b><p>Pantau di menu Riwayat: success, pending, processing, failed, atau refunded. Setiap perubahan diiringi notifikasi otomatis.</p><p class="muted" style="margin-top:8px">Hasil: bukti transaksi tersimpan dengan nomor referensi unik.</p></div></li>
  </ol>
  <div class="cards3" style="margin-top:18px">
    <div class="card reveal"><?= icon('qr') ?><h3>Isi saldo dulu</h3><p>Deposit via QRIS: minimal sesuai batas yang tampil, bayar sebelum QR kedaluwarsa, saldo masuk otomatis.</p></div>
    <div class="card reveal" style="--d:.07s"><?= icon('lock') ?><h3>Siapkan PIN</h3><p>Buat PIN 6 digit berbeda dari kata sandi. Semua pembayaran, transfer, dan penarikan membutuhkannya.</p></div>
    <div class="card reveal" style="--d:.14s"><?= icon('receipt') ?><h3>Simpan referensi</h3><p>Setiap transaksi punya nomor referensi (DEP, TRF, PPOB, WD). Catat untuk pelacakan dan bantuan.</p></div>
  </div>
  <div class="reveal steps-cta" style="margin-top:22px">
    <a class="btn btn-primary" href="<?= url('register') ?>">Mulai Langkah 1 <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('faq') ?>">Masih ada pertanyaan?</a>
  </div>
</div></section>
