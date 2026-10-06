# Keputusan teknis FanzPay (ADRs ringkas)

1. **PHP native tanpa framework.** Target deploy cPanel shared hosting tanpa
   SSH/composer; satu front controller + autoloader PSR-4 sederhana memberi
   struktur modular (Core/Services/Providers/Controllers) tanpa ketergantungan build.
2. **Satu controller depan untuk web+API+webhook.** Konsisten base path
   (subfolder XAMPP maupun root cPanel) via deteksi `SCRIPT_NAME` + `APP_URL`.
3. **Database `fanzpay_web`, bukan `fanzpay`.** Nama `fanzpay` sudah dipakai
   proyek Laravel lain (`D:\xampp\htdocs\fanzpay-app`) di mesin dev; memakai
   database terpisah mencegah tabrakan destruktif.
4. **Nominal = INT rupiah.** Tanpa float; `WalletService::append` satu-satunya
   penulis saldo, selalu `SELECT … FOR UPDATE` + ledger append-only dengan
   unique key `(transaction_id, user_id, direction)`.
5. **Idempotency dua lapis.** Header/kolom `X-Idempotency-Key` di sisi aplikasi
   + unique constraint di DB sebagai jaring pengaman benturansubmit/webhook ganda.
6. **PIN ≠ password.** Tabel & hash terpisah, throttling + lockout sendiri,
   audit tiap pakai.
7. **Provider terisolasi.** `BuatQrisClient`/`DigiFlazzClient` satu-satunya
   tempat yang tahu endpoint & signature; service bisnis hanya memanggil
   metode generik. Kredensial hanya dari env/pengaturan server-side.
8. **Mock jujur.** Tanpa kredensial → `MockProvider` deterministik + banner
   "mode sandbox" di semua permukaan; tidak ada transaksi yang diklaim sukses nyata.
9. **Harga jual vs modal.** `PricingService` terpisah dari katalog; API publik
   dan UI user tidak pernah memuat `base_price`/`margin` (kolom hanya di-SELECT
   di panel super admin).
10. **View `extract()` aman-collision.** Variabel internal renderer memakai
    prefix `$__fp_` setelah ditemukan bug payload ber-key `data` yang hilang
    oleh `EXTR_SKIP` (tertangkap validasi rute admin/rekonsiliasi + regression test).
11. **Rate limiter tanpa transaksi eksplisit.** `REPLACE/UPDATE` tunggal sudah
    atomic dan aman dipanggil di dalam transaksi bisnis (sebelumnya nested
    `beginTransaction` merusak transaksi luar — tertangkap test PIN).
12. **Android native Kotlin.** Satu bahasa, satu toolchain (Android Studio),
    Retrofit + DataStore; QR scan ML Kit + generate ZXing; tanpa secret di APK.
13. **Tidak ada klaim "anti-hack".** Fokus: surface kecil, serangan umum dicegah,
    semua aksi sensitif teraudit, kegagalan aman (fail-closed untuk uang).
14. **Nol data demo.** Tidak ada akun/produk bawaan; super admin pertama dibuat
    via `php web/create-admin.php` (atau `SUPERADMIN_*` di `.env` saat install).
    Katalog 100% dari DigiFlazz via `CatalogSyncService` (dipakai CLI `sync.php`
    maupun tombol panel admin).
15. **Sinkronisasi hemat & tahan limit.** Price-list DigiFlazz di-rate-limit
    (`rc 83` bila berlebihan) → client menolak `rc != 00` dengan pesan jelas,
    sync memakai prepared statement ulang + commit per 300 baris (ribuan SKU
    dalam detik), dan dijadwalkan harian — bukan tiap menit.
16. **Status provider boolean.** DigiFlazz mengirim `buyer/seller_product_status`
    sebagai boolean, bukan string "Aktif" → normalisasi `isOn/isOff` menerima
    keduanya agar produk real tidak salah nonaktif massal.
