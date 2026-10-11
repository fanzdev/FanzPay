<?php
/** Halaman Layanan — daftar layanan PPOB FanzPay (data real dari kategori aktif). */
/** @var array $categories */ /** @var int $catalog_total */
$detail = [
  'pulsa'  => ['icon' => 'smartphone', 'name' => 'Pulsa', 'desc' => 'Isi ulang pulsa prabayar semua operator. Masukkan nomor HP tujuan, pilih nominal, konfirmasi dengan PIN.'],
  'data'   => ['icon' => 'wifi', 'name' => 'Paket Data', 'desc' => 'Paket internet harian, mingguan, dan bulanan. Pilih paket sesuai kebutuhan dan nomor tujuan.'],
  'pln'    => ['icon' => 'zap', 'name' => 'Listrik PLN', 'desc' => 'Token prabayar dengan nomor meter ID dan pembayaran tagihan pascabayar dengan ID pelanggan.'],
  'emoney' => ['icon' => 'wallet', 'name' => 'Top Up E-Wallet', 'desc' => 'Isi saldo e-money dan dompet digital. Pastikan nomor tujuan sudah benar sebelum membayar.'],
  'game'   => ['icon' => 'gamepad', 'name' => 'Voucher Game', 'desc' => 'Voucher dan item game dari katalog resmi. Kode dan instruksi redeem tampil setelah transaksi berhasil.'],
  'tv'     => ['icon' => 'tv', 'name' => 'TV dan Internet', 'desc' => 'Pembayaran langganan TV kabel dan internet sesuai nomor pelanggan.'],
  'pdam'   => ['icon' => 'droplet', 'name' => 'PDAM', 'desc' => 'Pembayaran tagihan air PDAM sesuai wilayah dan nomor pelanggan.'],
  'bpjs'   => ['icon' => 'shieldCheck', 'name' => 'BPJS', 'desc' => 'Pembayaran iuran BPJS Kesehatan dan Ketenagakerjaan dengan nomor VA keluarga.'],
];
?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Layanan FanzPay</span>
  <h1>Semua layanan dalam <span class="grad-text">satu saldo.</span></h1>
  <p class="lead">Delapan kelompok layanan PPOB yang bisa dibeli dari dashboard setelah masuk.<?php if ((int)$catalog_total > 0): ?> Katalog aktif saat ini memuat <strong><?= number_format((int)$catalog_total) ?> produk</strong>.<?php endif; ?></p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="<?= url('register') ?>">Daftar untuk Bertransaksi <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('harga') ?>">Lihat Harga dan Biaya</a>
  </div>
</div></section>

<section class="sect"><div class="wrap">
  <div class="layanan-list">
    <?php foreach ($detail as $code => $d): ?>
    <article class="layanan-detail reveal" id="<?= e($code) ?>">
      <span class="layanan-ic"><?= icon($d['icon'], 24) ?></span>
      <div>
        <h2><?= e($d['name']) ?></h2>
        <p><?= e($d['desc']) ?></p>
        <?php if (!empty($categories)): ?>
          <?php $match = array_values(array_filter($categories, fn($c) => ($c['code'] ?? '') === $code)); ?>
          <?php if ($match): ?><p class="muted" style="font-size:13.5px;margin:0">Kategori aktif: <strong><?= e($match[0]['name']) ?></strong>.</p><?php endif; ?>
        <?php endif; ?>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= url('login') ?>">Beli via Dashboard <?= icon('arrowR', 15) ?></a>
    </article>
    <?php endforeach; ?>
    <article class="layanan-detail reveal" id="emoney-tunai">
      <span class="layanan-ic"><?= icon('grid', 24) ?></span>
      <div>
        <h2>Produk Digital Lainnya</h2>
        <p>Katalog tambahan mengikuti sinkronisasi provider resmi. Hanya produk aktif dan terverifikasi yang tampil di dashboard — tanpa daftar palsu.</p>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= url('login') ?>">Buka Katalog <?= icon('arrowR', 15) ?></a>
    </article>
  </div>
  <div class="card reveal page-note">
    <h3>Cara membeli layanan</h3>
    <p>Masuk ke dashboard, pilih produk, masukkan nomor tujuan, periksa harga final, konfirmasi dengan PIN 6 digit, lalu pantau status di menu Riwayat. Panduan lengkap ada di <a href="<?= url('cara-kerja') ?>">Cara Kerja</a> dan <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>.</p>
  </div>
</div></section>
