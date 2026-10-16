<?php
/**
 * Landing FanzPay v4 — editorial fintech profesional.
 * 1 Hero (proposisi + statistik nyata) · 2 Strip kepercayaan · 3 Layanan
 * 4 Cara kerja (stepper) · 5 Keunggulan (split editorial) · 6 Biaya (tabel)
 * 7 FAQ ringkas · 8 Unduh APK (data nyata) · 9 CTA akhir.
 * Hanya ikon SVG via icon(). Tanpa data demo — angka memakai variabel real.
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

$hasCatalog = (int)$catalog_total > 0;
$apkOk = !empty($apk['exists']);
?>
<!-- 1. HERO -->
<section class="lp-hero"><div class="wrap lp-grid">
  <div class="anim-fadeUp">
    <span class="eyebrow"><span class="pulse"></span> Dompet Digital &amp; PPOB Indonesia</span>
    <h1 class="lp-h1">Satu saldo untuk semua kebutuhan <span class="grad-text">digital dan tagihanmu.</span></h1>
    <p class="lp-lead">FanzPay adalah <strong>dompet digital dan layanan PPOB</strong>: isi saldo via QRIS real-time, <strong>transfer gratis</strong> antar pengguna, tarik dana ke bank dan e-wallet, lalu beli 8 layanan — harga final tampil di awal, setiap transaksi tercatat rapi.</p>
    <div class="lp-cta">
      <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Mulai Sekarang <?= icon('arrowR', 18) ?></a>
      <a class="btn btn-ghost btn-lg" href="<?= url('layanan') ?>">Lihat Layanan</a>
    </div>
    <ul class="lp-assure">
      <li><?= icon('checkCircle', 17) ?><span>Registrasi gratis, verifikasi email</span></li>
      <li><?= icon('checkCircle', 17) ?><span>PIN 6 digit tiap transaksi sensitif</span></li>
      <li><?= icon('checkCircle', 17) ?><span>Web responsif + aplikasi Android</span></li>
    </ul>
    <div class="lp-stats">
      <?php if ($hasCatalog): ?>
        <div><b data-count="<?= (int)$catalog_total ?>"><?= number_format((int)$catalog_total) ?></b><small>Produk aktif</small></div>
        <div><b data-count="<?= (int)$cat_total ?>"><?= number_format((int)$cat_total) ?></b><small>Kategori PPOB</small></div>
      <?php else: ?>
        <div><b>8</b><small>Layanan PPOB</small></div>
        <div><b>1</b><small>Saldo terpadu</small></div>
      <?php endif; ?>
      <div><b>Rp0</b><small>Biaya transfer</small></div>
      <div><b><?= rupiah((int)($min_deposit ?? 10000)) ?></b><small>Deposit mulai</small></div>
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

<!-- 2. STRIP KEPERCAYAAN -->
<section class="sect" style="padding-top:34px"><div class="wrap">
  <div class="lp-strip reveal">
    <div class="lp-strip-item"><?= icon('zap', 20) ?><div><b>Kemudahan Transaksi</b><small>Alur singkat, status jelas setiap langkah.</small></div></div>
    <div class="lp-strip-item"><?= icon('tag', 20) ?><div><b>Transparansi Harga</b><small>Harga final di awal, tanpa biaya tersembunyi.</small></div></div>
    <div class="lp-strip-item"><?= icon('receipt', 20) ?><div><b>Riwayat Lengkap</b><small>Semua aktivitas tersimpan dan mudah ditelusuri.</small></div></div>
    <div class="lp-strip-item"><?= icon('lock', 20) ?><div><b>Keamanan Berlapis</b><small>PIN terpisah, OTP email, dan audit log.</small></div></div>
    <div class="lp-strip-item"><?= icon('headset', 20) ?><div><b>Bantuan Siaga</b><small>Pusat bantuan dan panduan mandiri.</small></div></div>
  </div>
</div></section>

<!-- 3. LAYANAN PPOB -->
<section class="sect" id="layanan"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Layanan PPOB</span>
    <h2>Delapan layanan dalam satu saldo.</h2>
    <p class="sub">Tersedia lewat dashboard setelah masuk.<?php if ($hasCatalog): ?> Saat ini <strong><?= number_format((int)$catalog_total) ?> produk</strong> dalam <strong><?= number_format((int)$cat_total) ?> kategori</strong> aktif.<?php else: ?> Katalog dimuat langsung dari provider resmi — harga final tampil setelah sinkronisasi.<?php endif; ?></p>
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
          <div class="prod-tx"><b><?= e($f['name']) ?></b><small><?= e($f['cat_name'] ?? '') ?> · harga final</small></div>
          <span class="prod-price"><?= isset($f['price']) && (int)$f['price'] > 0 ? rupiah((int)$f['price']) : 'Lihat' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div></section>

<!-- 4. CARA KERJA -->
<section class="sect steps-sect" id="cara-kerja"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Cara Kerja</span>
    <h2>Lima langkah sampai transaksi berhasil.</h2>
    <p class="sub">Alurnya sama di web dan aplikasi Android — semua dilakukan dari dashboard setelah masuk.</p>
  </div>
  <ol class="lp-steps">
    <li class="reveal"><span class="lp-step-n">1</span><div><b>Registrasi akun</b><p>Gratis dengan nama, nomor HP, email, dan kata sandi, lalu verifikasi email.</p></div></li>
    <li class="reveal" style="--d:.06s"><span class="lp-step-n">2</span><div><b>Pilih layanan</b><p>Buka menu produk di dashboard dan pilih yang dibutuhkan.</p></div></li>
    <li class="reveal" style="--d:.12s"><span class="lp-step-n">3</span><div><b>Isi nomor tujuan</b><p>Nomor HP, ID meter PLN, atau nomor pelanggan — periksa dua kali.</p></div></li>
    <li class="reveal" style="--d:.18s"><span class="lp-step-n">4</span><div><b>Bayar dengan PIN</b><p>Konfirmasi PIN 6 digit. Saldo terpotong persis harga final.</p></div></li>
    <li class="reveal" style="--d:.24s"><span class="lp-step-n">5</span><div><b>Pantau status</b><p>Lihat di Riwayat. Gagal otomatis kembali sebagai refund.</p></div></li>
  </ol>
  <div class="reveal lp-steps-cta">
    <a class="btn btn-primary" href="<?= url('register') ?>">Daftar Sekarang <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('login') ?>">Sudah punya akun? Masuk</a>
  </div>
</div></section>

<!-- 5. KEUNGGULAN -->
<section class="sect" id="keunggulan"><div class="wrap lp-split">
  <div class="reveal lp-split-head">
    <span class="eyebrow">Keunggulan FanzPay</span>
    <h2>Satu platform, tanpa drama.</h2>
    <p class="sub">Semua di bawah ini sudah aktif dan bisa dipakai — bukan rencana, bukan ilustrasi.</p>
    <div class="lp-split-cta">
      <a class="btn btn-primary" href="<?= url('register') ?>">Coba Gratis <?= icon('arrowR', 17) ?></a>
      <a class="btn btn-ghost" href="<?= url('keunggulan') ?>">Selengkapnya</a>
    </div>
  </div>
  <ul class="lp-feats">
    <li class="reveal"><span class="lp-feat-ic"><?= icon('grid', 20) ?></span><div><b>Satu akun multi-kebutuhan</b><p>Dompet, PPOB, transfer, dan penarikan dalam satu saldo.</p></div></li>
    <li class="reveal"><span class="lp-feat-ic"><?= icon('tag', 20) ?></span><div><b>Harga final di awal</b><p>Nominal yang tampil adalah yang terpotong. Margin terbuka, tanpa biaya siluman.</p></div></li>
    <li class="reveal"><span class="lp-feat-ic"><?= icon('receipt', 20) ?></span><div><b>Riwayat + notifikasi otomatis</b><p>Setiap deposit, transfer, PPOB, dan penarikan tercatat dengan referensi unik.</p></div></li>
    <li class="reveal"><span class="lp-feat-ic"><?= icon('wallet', 20) ?></span><div><b>Deposit QRIS real-time</b><p>Mulai <?= rupiah((int)($min_deposit ?? 10000)) ?>, saldo masuk otomatis setelah terverifikasi.</p></div></li>
    <li class="reveal"><span class="lp-feat-ic"><?= icon('swap', 20) ?></span><div><b>Transfer Rp0 via nomor atau QR</b><p>Kirim ke sesama pengguna seketika — gratis untuk pengirim maupun penerima.</p></div></li>
    <li class="reveal"><span class="lp-feat-ic"><?= icon('smartphone', 20) ?></span><div><b>Web responsif + aplikasi Android</b><p>Dashboard menyesuaikan layar, plus APK resmi untuk pelanggan.</p></div></li>
  </ul>
</div></section>

<!-- 6. BIAYA -->
<section class="sect harga-sect" id="harga"><div class="wrap">
  <div class="reveal sect-head">
    <span class="eyebrow">Harga dan Biaya</span>
    <h2>Biaya yang jelas sebelum kamu bayar.</h2>
    <p class="sub">Kebijakan biaya inti FanzPay. Rincian lengkap ada di halaman <a href="<?= url('harga') ?>">Harga dan Biaya</a>.</p>
  </div>
  <div class="reveal lp-fee"><table>
    <tr><th>Layanan</th><th>Ketentuan</th><th>Biaya</th></tr>
    <tr><td>Transfer antar pengguna<small>Via nomor HP / ID / QR</small></td><td>Minimal <?= rupiah((int)($min_transfer ?? 1000)) ?> · masuk seketika</td><td class="num">Rp0</td></tr>
    <tr><td>Deposit QRIS<small>Verifikasi otomatis</small></td><td><?= rupiah((int)($min_deposit ?? 10000)) ?> – <?= rupiah((int)($max_deposit ?? 5000000)) ?> per transaksi</td><td class="num">Biaya provider tampil di awal</td></tr>
    <tr><td>Tarik dana<small>Bank &amp; e-wallet tersimpan</small></td><td>Minimal <?= rupiah((int)($min_withdraw ?? 20000)) ?> · status terpantau</td><td class="num">Sesuai ketentuan penarikan</td></tr>
    <tr><td>Produk PPOB<small>Pulsa, data, PLN, BPJS, game</small></td><td>Harga final tampil sebelum bayar</td><td class="num">Tanpa biaya tersembunyi</td></tr>
    <tr><td>Transaksi gagal<small>Otomatis</small></td><td>Dana kembali ke saldo, tercatat sebagai refund</td><td class="num">Rp0</td></tr>
  </table></div>
  <div class="reveal lp-fee-note">
    <?= icon('shieldCheck', 18) ?>
    <span><strong>Janji kami:</strong> nominal yang kamu setujui adalah nominal yang terpotong. Tidak lebih.</span>
    <a href="<?= url('harga') ?>">Lihat rincian <?= icon('arrowR', 15) ?></a>
  </div>
</div></section>

<!-- 7. FAQ -->
<section class="sect faq" id="faq"><div class="wrap" style="max-width:820px">
  <div class="reveal sect-head" style="text-align:center">
    <span class="eyebrow">FAQ</span>
    <h2>Enam jawaban paling dicari.</h2>
    <p class="sub" style="margin-left:auto;margin-right:auto">Panduan lengkap ada di halaman <a href="<?= url('faq') ?>">FAQ</a> dan <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>.</p>
  </div>
  <details class="reveal" open><summary>Apa itu FanzPay?</summary><p class="muted">Dompet digital dan layanan PPOB: satu akun untuk menyimpan saldo, transfer antar pengguna, tarik dana, dan membeli pulsa, paket data, listrik PLN, BPJS, e-money, dan voucher game.</p></details>
  <details class="reveal"><summary>Bagaimana cara mendaftar?</summary><p class="muted">Isi nama, nomor HP, email, dan kata sandi di halaman Registrasi, verifikasi kode OTP email, lalu buat PIN 6 digit untuk transaksi.</p></details>
  <details class="reveal"><summary>Bagaimana cara mengisi saldo?</summary><p class="muted">Buka menu Deposit di dashboard, pilih nominal mulai <?= rupiah((int)($min_deposit ?? 10000)) ?>, pindai QRIS yang muncul. Saldo masuk otomatis setelah terverifikasi.</p></details>
  <details class="reveal"><summary>Bagaimana cara bertransaksi?</summary><p class="muted">Pilih produk, masukkan nomor tujuan, periksa harga final, konfirmasi dengan PIN, lalu pantau statusnya di menu Riwayat.</p></details>
  <details class="reveal"><summary>Apa yang terjadi jika transaksi gagal?</summary><p class="muted">Dana otomatis kembali ke saldo dan tercatat sebagai refund. Kamu menerima notifikasi dan bisa melihat rinciannya di Riwayat.</p></details>
  <details class="reveal"><summary>Berapa biaya transfer saldo?</summary><p class="muted">Gratis — Rp0. Kirim via nomor HP atau pindai QR penerima, minimal <?= rupiah((int)($min_transfer ?? 1000)) ?>, dana masuk seketika.</p></details>
</div></section>

<!-- 8. UNDUH APK -->
<section class="sect" style="padding-top:0"><div class="wrap">
  <div class="reveal lp-apk">
    <span class="lp-apk-ic"><?= icon('download', 24) ?></span>
    <div class="lp-apk-tx"><b>Aplikasi Android FanzPay</b><small><?php if ($apkOk): ?>Versi <?= e($apk['version'] ?: 'terbaru') ?><?= $apk['size_human'] ? ' · ' . e($apk['size_human']) : '' ?> · gratis<?php else: ?>Gratis · deposit, transfer, dan PPOB dari HP<?php endif; ?></small></div>
    <a class="btn btn-primary" href="<?= url('download-apk') ?>"><?= icon('download', 17) ?> Unduh APK</a>
  </div>
</div></section>

<!-- 9. CTA -->
<section class="sect cta-sect"><div class="wrap">
  <div class="lp-cta reveal">
    <div class="lp-cta-tx">
      <span class="eyebrow eyebrow-light">Mulai dalam hitungan menit</span>
      <h2>Siap memakai FanzPay untuk kebutuhan harianmu?</h2>
      <p>Daftar gratis, verifikasi email, isi saldo pertama via QRIS — semua layanan langsung terbuka dalam satu dashboard.</p>
      <div class="cta-btns">
        <a class="btn btn-primary btn-lg" href="<?= url('register') ?>">Daftar Sekarang <?= icon('arrowR', 18) ?></a>
        <a class="btn btn-ghost btn-lg cta-login" href="<?= url('login') ?>">Masuk ke Akun</a>
      </div>
    </div>
    <div class="lp-cta-side">
      <div class="lp-cta-stat"><b>Rp0</b><small>Transfer antar pengguna, selamanya</small></div>
      <div class="lp-cta-stat"><b><?= $hasCatalog ? number_format((int)$catalog_total) . ' produk' : '8 layanan' ?></b><small><?= $hasCatalog ? 'Aktif dalam ' . number_format((int)$cat_total) . ' kategori' : 'Dalam satu saldo terpadu' ?></small></div>
      <div class="lp-cta-stat"><b>QRIS</b><small>Deposit real-time terverifikasi otomatis</small></div>
    </div>
  </div>
</div></section>
