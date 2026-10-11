<?php /** Kebijakan Privasi FanzPay. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow">Legal</span>
  <h1>Kebijakan <span class="grad-text">Privasi.</span></h1>
  <p class="lead">Terakhir diperbarui: <?= date('Y') ?>. Dokumen ini menjelaskan data yang kami kumpulkan dan cara kami melindunginya.</p>
</div></section>
<section class="sect"><div class="wrap legal-wrap">
  <article class="card reveal">
    <h3>1. Data yang Dikumpulkan</h3><p>Nama, nomor HP, email, kata sandi terenkripsi (hash), PIN transaksi terenkripsi, data wallet dan transaksi, tujuan penarikan, serta log keamanan (IP, perangkat, waktu akses).</p>
    <h3>2. Penggunaan Data</h3><p>Data dipakai untuk menyediakan layanan dompet dan PPOB, verifikasi identitas, pencegahan penipuan, notifikasi transaksi, dan pelaporan internal. Kredensial provider tidak pernah disimpan di perangkat pengguna.</p>
    <h3>3. Penyimpanan dan Keamanan</h3><p>Kata sandi dan PIN disimpan sebagai hash satu arah. Aksi sensitif wajib PIN. Akses admin tercatat di audit log dan dibatasi permission granular.</p>
    <h3>4. Berbagi Data</h3><p>Data transaksi yang diperlukan diteruskan ke provider pembayaran (misalnya penyedia QRIS dan agregator PPOB) semata untuk memproses transaksi. Tidak ada penjualan data ke pihak ketiga.</p>
    <h3>5. Hak Pengguna</h3><p>Pengguna dapat melihat dan memperbarui profil, meminta riwayat transaksinya, serta mengajukan penghapusan akun sesuai ketentuan retensi regulasi keuangan.</p>
    <h3>6. Kontak Privasi</h3><p>Pertanyaan privasi dapat diajukan melalui <a href="<?= url('bantuan') ?>">Pusat Bantuan</a> dengan menyertakan email akun terdaftar.</p>
  </article>
</div></section>
