<?php /** Halaman FAQ — 9 pertanyaan umum + tautan bantuan. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Pertanyaan Umum</span>
  <h1>Jawaban jelas atas <span class="grad-text">hal yang sering ditanyakan.</span></h1>
  <p class="lead">Sembilan topik utama seputar akun, saldo, dan transaksi FanzPay. Butuh panduan bergambar? Buka <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>.</p>
</div></section>

<section class="sect faq"><div class="wrap" style="max-width:860px">
  <details class="reveal" open><summary>1. Apa itu FanzPay?</summary><p class="muted">FanzPay adalah dompet digital dan layanan PPOB Indonesia. Dengan satu akun kamu bisa menyimpan saldo, transfer ke sesama pengguna tanpa biaya, menarik dana ke bank dan e-wallet, serta membeli pulsa, paket data, token dan tagihan PLN, BPJS, top up e-money, dan voucher game.</p></details>
  <details class="reveal"><summary>2. Bagaimana cara mendaftar?</summary><p class="muted">Buka halaman Registrasi, isi nama lengkap, nomor HP aktif, email aktif, dan kata sandi minimal 8 karakter. Masukkan kode OTP yang dikirim ke email untuk verifikasi, lalu buat PIN 6 digit yang dipakai untuk setiap transaksi sensitif.</p></details>
  <details class="reveal"><summary>3. Bagaimana cara mengisi saldo?</summary><p class="muted">Masuk ke dashboard, buka menu Deposit, masukkan nominal sesuai batas yang tampil, lalu pindai kode QRIS yang dibuat sistem. Saldo masuk otomatis setelah pembayaran terverifikasi oleh provider. Status pending, berhasil, atau kedaluwarsa bisa dipantau di menu Deposit dan Riwayat.</p></details>
  <details class="reveal"><summary>4. Bagaimana cara melakukan transaksi?</summary><p class="muted">Pilih produk di menu Produk dashboard, masukkan nomor atau ID tujuan, periksa nama produk dan harga final, konfirmasi pembayaran dengan PIN 6 digit, lalu periksa status di menu Riwayat dan notifikasi.</p></details>
  <details class="reveal"><summary>5. Apa yang terjadi jika transaksi gagal?</summary><p class="muted">Dana otomatis dikembalikan ke saldo dan status transaksi berubah menjadi refund, gagal, atau reversed. Kamu menerima notifikasi dan dapat melihat referensi pengembalian di Riwayat. Tidak ada dana yang hilang sepihak.</p></details>
  <details class="reveal"><summary>6. Berapa lama proses transaksi?</summary><p class="muted">Sebagian besar pembelian PPOB selesai dalam hitungan detik hingga beberapa menit mengikuti respons provider. Jika status masih pending atau processing, tunggu notifikasi berikutnya dan hindari mengulang pembayaran yang sama.</p></details>
  <details class="reveal"><summary>7. Bagaimana cara transfer saldo?</summary><p class="muted">Buka menu Transfer, masukkan nomor HP penerima atau pindai QR Code-nya, isi nominal, lalu konfirmasi dengan PIN. Transfer antar pengguna gratis (Rp0) dan tercatat di Riwayat kedua belah pihak.</p></details>
  <details class="reveal"><summary>8. Bagaimana cara mengamankan akun?</summary><p class="muted">Gunakan kata sandi unik, rahasiakan PIN 6 digit, pastikan email terverifikasi, aktifkan kunci layar perangkat, dan selalu keluar dari dashboard di perangkat bersama. Segera ganti kata sandi jika ada aktivitas yang tidak dikenal.</p></details>
  <details class="reveal"><summary>9. Bagaimana cara menghubungi customer service?</summary><p class="muted">Buka halaman Pusat Bantuan untuk panduan mandiri dan kanal kontak resmi. Siapkan email akun, nomor HP, dan nomor referensi transaksi agar penanganan lebih cepat.</p></details>
  <div class="reveal steps-cta" style="margin-top:22px">
    <a class="btn btn-primary" href="<?= url('bantuan') ?>">Buka Pusat Bantuan <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('register') ?>">Daftar Sekarang</a>
  </div>
</div></section>
