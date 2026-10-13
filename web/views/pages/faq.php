<?php /** Halaman FAQ — 9 pertanyaan umum + tautan bantuan. Q&A dirender dari $faq_items (controller) agar selaras dengan FAQPage JSON-LD. */ ?>
<section class="hero hero-page"><div class="wrap page-head anim-fadeUp">
  <span class="eyebrow"><span class="pulse"></span> Pertanyaan Umum</span>
  <h1>Jawaban jelas atas <span class="grad-text">hal yang sering ditanyakan.</span></h1>
  <p class="lead">Sembilan topik utama seputar akun, saldo, dan transaksi FanzPay. Butuh panduan bergambar? Buka <a href="<?= url('bantuan') ?>">Pusat Bantuan</a>.</p>
</div></section>

<section class="sect faq"><div class="wrap" style="max-width:860px">
  <?php $fi = 0; foreach (($faq_items ?? []) as $it): $fi++; ?>
  <details class="reveal"<?= $fi === 1 ? ' open' : '' ?>><summary><?= $fi ?>. <?= e($it['q']) ?></summary><p class="muted"><?= e($it['a']) ?></p></details>
  <?php endforeach; ?>
  <div class="reveal steps-cta" style="margin-top:22px">
    <a class="btn btn-primary" href="<?= url('bantuan') ?>">Buka Pusat Bantuan <?= icon('arrowR', 17) ?></a>
    <a class="btn btn-ghost" href="<?= url('register') ?>">Daftar Sekarang</a>
  </div>
</div></section>
