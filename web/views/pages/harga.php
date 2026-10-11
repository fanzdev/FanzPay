<?php
/** Halaman Harga — informasi harga dan biaya transaksi (nilai real dari pengaturan). */
/** @var int $min_deposit */ /** @var int $max_deposit */ /** @var int $min_withdraw */ /** @var int $max_withdraw */ /** @var int $min_transfer */ /** @var int $max_transfer */
?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Harga dan Biaya</span>
  <h1>Transparan sejak <span class="grad-text">angka pertama.</span></h1>
  <p class="lead">Tidak ada biaya siluman. Semua nominal final ditampilkan sebelum kamu menekan tombol bayar.</p>
</div></section>

<section class="sect"><div class="wrap">
  <div class="harga-table-wrap reveal">
    <table class="tbl harga-tbl">
      <thead><tr><th>Transaksi</th><th>Ketentuan</th><th>Biaya</th></tr></thead>
      <tbody>
        <tr><td><strong>Harga produk PPOB</strong></td><td>Harga final tampil di katalog dan halaman konfirmasi.</td><td><span class="badge b-info">Sesuai katalog</span></td></tr>
        <tr><td><strong>Biaya administrasi</strong></td><td>Jika provider menetapkan admin fee, nominalnya dirinci terpisah.</td><td><span class="badge b-info">Ditampilkan rinci</span></td></tr>
        <tr><td><strong>Deposit via QRIS</strong></td><td>Minimal <?= rupiah((int)$min_deposit) ?> · maksimal <?= rupiah((int)$max_deposit) ?> per transaksi. QR dibuat di server, status terverifikasi webhook.</td><td><span class="badge b-info">Mengikuti provider QRIS</span></td></tr>
        <tr><td><strong>Transfer antar pengguna</strong></td><td>Minimal <?= rupiah((int)$min_transfer) ?> · maksimal <?= rupiah((int)$max_transfer) ?>. Via nomor HP atau QR Code.</td><td><span class="badge b-ok">Gratis Rp0</span></td></tr>
        <tr><td><strong>Penarikan saldo</strong></td><td>Minimal <?= rupiah((int)$min_withdraw) ?> · maksimal <?= rupiah((int)$max_withdraw) ?>. Ke bank dan e-wallet yang tersimpan di akun.</td><td><span class="badge b-info">Mengikuti proses bank</span></td></tr>
        <tr><td><strong>Pengembalian dana</strong></td><td>Transaksi gagal otomatis kembali ke saldo sebagai refund tercatat.</td><td><span class="badge b-ok">Gratis</span></td></tr>
        <tr><td><strong>Transaksi gagal</strong></td><td>Status gagal, kedaluwarsa, atau ditolak dijelaskan di Riwayat. Tidak ada potongan sepihak.</td><td><span class="badge b-ok">Dana kembali</span></td></tr>
      </tbody>
    </table>
  </div>
  <div class="cards3" style="margin-top:18px">
    <div class="card reveal"><h3>Ketentuan deposit</h3><p>Buat QRIS dari dashboard, bayar sebelum kedaluwarsa, saldo masuk otomatis setelah terverifikasi. Jangan transfer ke QR yang sudah kedaluwarsa.</p></div>
    <div class="card reveal" style="--d:.07s"><h3>Ketentuan penarikan</h3><p>Simpan tujuan bank atau e-wallet, ajukan penarikan dengan PIN, pantau status pending, disetujui, atau ditolak di Riwayat.</p></div>
    <div class="card reveal" style="--d:.14s"><h3>Kebijakan refund</h3><p>Detail mekanisme pengembalian ada di <a href="<?= url('kebijakan-refund') ?>">Kebijakan Refund</a>. Setiap refund memiliki referensi dan notifikasi tersendiri.</p></div>
  </div>
  <div class="reveal steps-cta">
    <a class="btn btn-primary" href="<?= url('register') ?>">Mulai Sekarang <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('faq') ?>">Lihat FAQ</a>
  </div>
</div></section>
