# FanzPay — Dompet Digital & PPOB

Platform PPOB + dompet digital: deposit via QRIS (BuatQris), transfer **gratis** antar
pengguna (nomor HP / QR), withdrawal ke bank & e-wallet, pembelian produk digital
(DigiFlazz), 3 role (`super_admin`, `admin`, `user`), ledger append-only, webhook
terverifikasi HMAC-SHA256, dan idempotency di semua alur finansial.

Prinsip: **"Simple interface, sophisticated system."**

```
fanzpay/
├── README.md            ← dokumen ini
├── logo.webp            ← logo resmi (sumber identitas visual)
├── prompt/              ← brief & dokumen Open API BuatQris (arsip)
├── web/                 ← APLIKASI WEB (PHP native + MySQL, siap XAMPP & cPanel)
│   ├── index.php        ← front controller (web + /api/* + /webhooks/*)
│   ├── .htaccess        ← rewrite + proteksi .env
│   ├── .env             ← kredensial lokal (JANGAN di-commit)
│   ├── .env.example     ← contoh konfigurasi
│   ├── install.php      ← installer CLI (buat DB + pengaturan)
│   ├── create-admin.php ← buat super_admin/admin (tidak ada akun bawaan)
│   ├── sync.php         ← tarik katalog REAL DigiFlazz via CLI
│   ├── config/          ← loader .env (server-side only)
│   ├── database/        ← schema.sql + seed.sql
│   ├── app/
│   │   ├── Core/        ← DB, Auth+RBAC, CSRF, Session, Validator,
│   │   │                  RateLimiter, Router, View, Logger
│   │   ├── Services/    ← Wallet, Deposit, Withdraw, Transfer, Ppob,
│   │   │                  Pricing, Pin, Otp, Notification, Settings, Reconcile
│   │   ├── Providers/   ← BuatQrisClient, DigiFlazzClient, MockProvider
│   │   └── Http/Controllers/ ← Web, Auth, User, Admin, Api, Webhook
│   ├── views/           ← layout public/auth/app/admin + 35 halaman
│   ├── public/assets/   ← css/app.css + js/app.js (tanpa build step)
│   ├── webhooks/        ← kompatibilitas cPanel
│   ├── cron/            ← reconcile.php (cron job)
│   ├── storage/logs/    ← log terstruktur (rahasia di-redact)
│   └── tests/           ← suite uji (php tests/run.php)
├── android/             ← APLIKASI ANDROID (Kotlin + Compose + Retrofit)
└── docs/                ← API contract, deployment, keputusan teknis
```

---

## 1. Menjalankan aplikasi web (XAMPP)

**Syarat:** XAMPP dengan Apache + MySQL + PHP ≥ 8.1 (ekstensi `pdo_mysql`, `curl`,
`mbstring`, `openssl` — bawaan XAMPP).

```powershell
# 1. Pastikan Apache & MySQL berjalan (XAMPP Control Panel)

# 2. Salin environment lalu sesuaikan bila perlu
copy web\.env.example web\.env

# 3. Install database (idempotent, aman diulang)
D:\xampp\php\php.exe web\install.php
```

> Database default: **`fanzpay_web`** (bukan `fanzpay`, karena nama itu dipakai
> proyek lain di mesin ini — lihat `docs/keputusan-teknis.md`). Ganti via `DB_*` di `.env`.

```powershell
# 4. Buka di browser
http://localhost/fanzpay/web/
```

**Tidak ada akun demo/bawaan.** Semua data 100% real (katalog dari DigiFlazz,
deposit QRIS asli). Buat super admin pertama via CLI:

```powershell
D:\xampp\php\php.exe web\create-admin.php "Nama Anda" anda@email.com 0812xxxxxxx "PasswordMin8"
# tambah argumen "admin" di akhir untuk membuat admin biasa (permission diatur di panel)
```

Lalu login di `http://localhost/fanzpay/web/login` dan tarik katalog real:

```powershell
D:\xampp\php\php.exe web\sync.php
# atau via panel: Admin → Produk → Sinkron sekarang
```

**Alur coba cepat (5 menit, dengan uang asli — mulai dari nominal kecil):**
1. Daftar akun user baru via halaman register (OTP ke email) → buat PIN.
2. **Deposit** nominal kecil (mis. Rp 10.000) → scan QRIS asli → saldo masuk
   otomatis setelah webhook terverifikasi.
3. **Produk** → pilih dari katalog DigiFlazz real → bayar pakai PIN.
4. Login super admin → **Command Center**, **Produk & Harga**, **Rekonsiliasi**.

**Menjalankan test:**

```powershell
D:\xampp\php\php.exe web\tests\run.php
# 11 lulus, 0 gagal — mencakup: rupiah, reference unik, HMAC webhook,
# signature DigiFlazz, scrub log, ledger append-only + rebuild,
# PIN lockout, dan regresi render view.
```

---

## 2. Provider: 100% real (BuatQris + DigiFlazz)

Kredensial production sudah terpasang di `web/.env` **lokal** (tidak ikut repo).
Yang perlu dilengkapi manual:

1. **BuatQris → Signing Secret**: dasbor → Open API → kartu Signing Secret →
   salin ke `BUATQRIS_SIGNING_SECRET` di `web/.env`. Tanpa ini webhook
   ditolak (deposit tidak akan terkonfirmasi otomatis).
2. **BuatQris → rekening & PIN penarikan**: verifikasi email akun → buat PIN Akun
   → daftarkan rekening → catat `wd_account_id` ke panel **Admin → API Config**.
   Webhook URL: `https://domain-anda/webhooks/buatqris`.
3. **DigiFlazz → isi saldo**: cek di panel **Command Center** (kartu saldo
   provider). Saldo 0 = pembelian PPOB akan gagal di provider. Top up dulu di
   dasbor DigiFlazz.
4. **Kunci API DigiFlazz**: kunci bertipe development yang terdaftar di akun
   terbukti valid (lolos cek-saldo). Satu kunci lain yang diberikan ditolak
   provider (`rc 41`) — jangan dipakai sebagai API key.

> **Wajib baca — rotasi token.** Secret BuatQris pernah dibagikan via chat AI
> saat setup. Setelah pengujian selesai: dasbor BuatQris → Open API →
> **Regenerate Token** (dan regenerate Signing Secret), lalu update `web/.env`.
> Token lama langsung mati. Jangan pernah commit `.env` atau menempel kredensial
> di issue/chat.

**Batas yang perlu diketahui:**
- DigiFlazz membatasi **refresh price-list** (bila melebihi, responsnya
  `rc 83`: "limitasi pengecekan pricelist"). Sinkronisasi cukup **1x sehari**
  via `php web/sync.php` / tombol panel / cron harian — bukan tiap menit.
- BuatQris: cek status maksimal **1x/20 detik per transaksi** — aplikasi memakai
  webhook + polling hemat 25 detik, jadi aman.
- Mode sandbox tetap tersedia untuk uji tanpa uang: set `BUATQRIS_SANDBOX=true` /
  `DIGIFLAZZ_SANDBOX=true` + `provider_mode=sandbox` (layar akan berlabel jelas).

---

## 3. Cron job (rekonsiliasi & deposit kedaluwarsa)

```bash
# cPanel → Cron Jobs, tiap 5 menit:
php /home/username/public_html/cron/reconcile.php
```

Idempotent: menandai deposit `pending` yang lewat `expired_at`, mencatat pending
lama ke log untuk ditindaklanjuti di panel **Rekonsiliasi**.

---

## 4. Deploy ke cPanel (tanpa Docker, tanpa Node)

1. cPanel → **Select PHP Version**: 8.1/8.2. **SSL**: aktif (wajib HTTPS).
2. Buat MySQL database + user → import `web/database/schema.sql`, lalu isi
   pengaturan awal via `web/database/seed.sql` + buat super admin manual
   (atau jalankan `install.php` sekali via SSH/cron lalu **hapus file-nya**).
3. Upload **isi folder `web/`** ke `public_html/` (atau subfolder). Tidak ada
   proses build — CSS/JS sudah statis.
4. Buat file `.env` dari `.env.example`, isi kredensial. Pastikan `.env`
   tidak bisa diakses browser (sudah dilindungi `.htaccess`; verifikasi manual).
5. Atur webhook BuatQris ke `https://domain/webhooks/buatqris`.
6. Nyalakan cron (bagian 3). Matikan `APP_DEBUG=false`.

Panduan langkah-per-langkah: `docs/deployment-cpanel.md`.

---

## 5. Aplikasi Android

Native Kotlin + Jetpack Compose + Retrofit + DataStore + CameraX/ML Kit,
mengonsumsi `web/api/*` yang sama (Bearer token, idempotency header otomatis).

### 5a. Dijalankan di emulator (cepat, untuk dev)

1. Buka folder `android/` di Android Studio → tunggu sync Gradle.
2. `API_BASE_URL` debug default `http://10.0.2.2/fanzpay/web/` (emulator → XAMPP). Run ▶.

### 5b. Dijalankan langsung di HP fisik (2 cara)

**Syarat awal (kedua cara):** HP dan PC satu Wi-Fi. Cari IP LAN PC
(`ipconfig` → IPv4, contoh `192.168.1.10`). Pastikan Windows Firewall
mengizinkan port 80, dan `http://192.168.1.10/fanzpay/web/` bisa dibuka dari
browser HP. Di `httpd-xampp.conf`, akses `/fanzpay` dari LAN harus diizinkan.

**Cara 1 — install APK langsung (tanpa kabel, paling praktis):**

1. Android Studio → **Build → Build App Bundle(s)/APK(s) → Build APK(s)**.
2. Ambil file `android/app/build/outputs/apk/debug/app-debug.apk`,
   kirim ke HP (USB/Bluetooth/Google Drive/WA ke diri sendiri).
3. Di HP: buka file → izinkan **Install unknown apps** → Install.
4. Buka FanzPay → di layar Login isi field **Server** dengan
   `http://192.168.1.10/fanzpay/web/` (ganti IP sesuai PC) → Masuk.
5. Untuk versi rapi: **Build → Generate Signed Bundle/APK** (buat keystore
   sekali, pakai seterusnya) → install APK release.

**Cara 2 — Run via USB debugging (langsung dari Android Studio):**

1. HP → **Settings → About phone → tap 7x Build number** (mode developer aktif)
   → **Developer options → USB debugging ON**.
2. Colok kabel USB → di HP pilih **File transfer** + **Allow USB debugging**
   (centang Always allow).
3. Android Studio → pilih HP di device dropdown → Run ▶. APK terinstall otomatis.
4. Isi field **Server** seperti Cara 1 (localhost HP ≠ localhost PC, jadi
   `10.0.2.2` tidak berlaku di HP fisik).

**Untuk production (wajib):** ganti Server ke `https://domain-anda/` dan set
`usesCleartextTraffic=false` di `AndroidManifest.xml` (saat ini `true` hanya
untuk kemudahan dev HTTP/XAMPP).

Fitur: login, beranda + saldo, produk + beli (PIN), transfer (resolve nama +
scan QR), deposit + polling status, withdraw, riwayat, notifikasi, QR saya
(generate offline), scan QR (CameraX + ML Kit, deep link `fanzpay://pay/u/{id}`).
Detail: `android/README.md`.

> Kredensial provider TIDAK ADA di aplikasi Android — semua request provider
> lewat backend. Yang disimpan di HP hanya Bearer token sesi.

---

## 6. Keamanan (ringkas)

PDO prepared statement, CSRF semua form, session regenerate + expiry 12 jam,
password `password_hash`, PIN 6 digit ter-hash + lockout 15 menit setelah 5x
salah, rate limit login/PIN/API, webhook HMAC wajib, idempotency key +
unique constraint di DB, ledger append-only + `SELECT … FOR UPDATE`,
audit log semua aksi sensitif, security header (nosniff, SAMEORIGIN frame,
HSTS saat HTTPS), log bebas rahasia (auto-redact). Yang TIDAK diklaim:
"tidak bisa diretas" — targetnya meminimalkan surface, mencegah serangan umum,
mendeteksi, dan menjaga auditabilitas. Daftar lengkap: `docs/keputusan-teknis.md`.

## 7. Batasan yang disengaja

- Email OTP memakai `mail()` PHP; di shared hosting butuh konfigurasi SMTP agar
  terkirim (di mode dev, kode tampil di layar + log).
- Polling status deposit dibatasi 1x/25 detik (menghormati limit BuatQris 1x/20 dtk);
  sumber kebenaran tetap webhook.
- Produk PPOB butuh kredensial DigiFlazz production untuk katalog real-time.
