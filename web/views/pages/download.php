<?php /** @var array $apk */ ?>
<section class="hero hero-page"><div class="wrap" style="max-width:880px">
  <div class="anim-fadeUp" style="text-align:center">
    <span class="eyebrow"><span class="pulse"></span> Aplikasi Android · Khusus pelanggan</span>
    <h1>Unduh <span class="grad-text">FanzPay</span> untuk Android.</h1>
    <p class="lead" style="margin-left:auto;margin-right:auto">Satu saldo, dua rumah: web dan Android sinkron mulus. Admin dan super admin tetap di web — lebih aman, lebih lengkap.</p>
  </div>
  <div class="card anim-pop" style="--d:.12s;margin-top:26px">
    <div class="dl-card">
      <div class="dl-icon"><?= icon('smartphone', 48) ?></div>
      <div>
        <h2 style="margin:0 0 6px">fanzpay.apk</h2>
        <?php if ($apk['exists']): ?>
          <p class="muted">File asli terbaru dari server ini · Ukuran <strong><?= e($apk['size_human']) ?></strong> · Gratis selamanya.</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
            <a class="btn btn-primary btn-lg" href="<?= url('download-apk') ?>?go=1"><?= icon('download', 18) ?> Unduh sekarang</a>
            <a class="btn btn-ghost" href="<?= url('register') ?>">Belum punya akun? Daftar</a>
          </div>
          <p class="muted" style="font-size:12.5px;margin:12px 0 0">Cara install: buka file, izinkan “Install unknown apps” sekali, pilih Install, lalu masuk dengan akun pelangganmu. Jika login menolak akun admin, itu disengaja — admin wajib lewat web.</p>
        <?php else: ?>
          <p class="muted">File APK belum diunggah ke server ini. Tenang — kamu tetap bisa pakai versi web yang super responsif, atau build sendiri dalam 3 menit:</p>
          <ol class="muted" style="font-size:14px;line-height:1.7">
            <li>Buka folder <b class="mono">android/</b> di Android Studio.</li>
            <li>Menu <b>Build &gt; Build APK(s)</b>, ambil <b class="mono">app-debug.apk</b>.</li>
            <li>Upload sebagai <b class="mono">web/public/assets/apk/fanzpay.apk</b> — tombol di halaman ini otomatis jadi link unduhan asli.</li>
          </ol>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
            <a class="btn btn-primary" href="<?= url('register') ?>">Pakai versi web dulu <?= icon('arrowR', 16) ?></a>
            <a class="btn btn-ghost" href="<?= url('') ?>"><?= icon('arrowL', 16) ?> Kembali ke beranda</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="cards3" style="margin-top:18px">
    <div class="card reveal"><?= icon('lock') ?><h3>Aman by design</h3><p>Bearer token 30 hari, PIN 6 digit, kredensial provider tidak pernah ada di HP.</p></div>
    <div class="card reveal" style="--d:.08s"><?= icon('zap') ?><h3>Sinkron real-time</h3><p>Deposit QRIS, transfer Rp0, PPOB — status diperbarui otomatis, hemat polling.</p></div>
    <div class="card reveal" style="--d:.16s"><?= icon('spark') ?><h3>Dibuat untuk harian</h3><p>Glassmorphism, dark dan light mode, gestur mulus, bahasa yang tidak kaku.</p></div>
  </div>
</div></section>
