<?php /** Kebijakan Refund FanzPay. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow">Legal</span>
  <h1>Kebijakan <span class="grad-text">Refund.</span></h1>
  <p class="lead">Terakhir diperbarui: <?= date('Y') ?>. Prinsip kami sederhana: dana yang gagal diproses harus kembali ke pengguna.</p>
</div></section>
<section class="sect"><div class="wrap legal-wrap">
  <article class="card reveal">
    <h3>1. Transaksi yang Direfund</h3><p>Pembelian PPOB yang gagal di provider, deposit yang tidak terverifikasi, dan penarikan yang ditolak akan dikembalikan ke saldo sebagai refund tercatat dengan referensi tersendiri.</p>
    <h3>2. Waktu Pengembalian</h3><p>Refund otomatis umumnya terjadi segera setelah status final gagal ditetapkan sistem. Penarikan yang ditolak admin atau provider dikembalikan setelah peninjauan.</p>
    <h3>3. Cara Memeriksa Refund</h3><p>Buka menu Riwayat, cari status refunded atau reversed, catat nomor referensi, dan periksa mutasi saldo serta notifikasi terkait.</p>
    <h3>4. Pengecualian</h3><p>Transaksi berstatus success dengan nomor tujuan benar tidak dapat direfund. Kesalahan input nomor tujuan menjadi tanggung jawab pengguna sesuai Syarat dan Ketentuan.</p>
    <h3>5. Sengketa</h3><p>Jika refund belum tercatat melebihi estimasi wajar, hubungi customer service via <a href="<?= url('bantuan') ?>">Pusat Bantuan</a> dengan nomor referensi dan bukti status.</p>
  </article>
</div></section>
