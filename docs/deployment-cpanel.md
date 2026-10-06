# Deploy FanzPay ke cPanel (langkah rinci)

## 1. Persiapan paket

- Upload **isi** `web/` ke `public_html/` (situs utama) atau
  `public_html/fanzpay/` (subfolder). Struktur akhir: `public_html/index.php`,
  `public_html/app/`, `public_html/views/`, …
- JANGAN upload: `prompt/`, `android/`, `docs/`, dan file `.env` lokal.

## 2. PHP & SSL

- cPanel → **Select PHP Version** → 8.1 atau 8.2, ekstensi: `pdo_mysql`, `curl`,
  `mbstring`, `openssl`, `json` (biasanya aktif).
- **SSL/TLS Status** → pasang sertifikat (AutoSSL/Let's Encrypt) + paksa HTTPS.

## 3. Database

- **MySQL Databases** → buat database (cth. `user_fanzpay`) + user + password,
  beri hak penuh ke database tersebut.
- **phpMyAdmin** → Import → `web/database/schema.sql`.
- Buat super admin (ganti hash!):
  ```sql
  INSERT INTO users (role,name,phone,email,password_hash,public_id,status,email_verified_at)
  VALUES ('super_admin','Super Admin','081200000001','super@domain',
    '$2y$10$…hash-dari-password…','FPSUPERADMIN','active',NOW());
  INSERT INTO wallets (user_id,balance,locked) VALUES (LAST_INSERT_ID(),0,0);
  ```
  Hash bisa dibuat sekali via `php -r "echo password_hash('Rahasia123!', PASSWORD_DEFAULT), PHP_EOL;"`
- Isi `system_settings` minimal:
  `site_name`, `provider_mode=sandbox`, limit deposit/withdraw/transfer
  (atau atur nanti via panel **Pengaturan**).

## 4. File `.env` production

Salin `.env.example` menjadi `.env` di samping `index.php`, isi:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda
DB_HOST=localhost
DB_NAME=user_fanzpay
DB_USER=user_fanzpay
DB_PASS=…
BUATQRIS_*=…   (bila sudah punya)
DIGIFLAZZ_*=…  (bila sudah punya)
```

Uji: buka `https://domain-anda/.env` → harus **403/404** (dilindungi `.htaccess`).
Jika server memakai Nginx/LiteSpeed tanpa `.htaccess`, blokir manual file `.env`.

## 5. Webhook & email

- BuatQris → Callback URL: `https://domain-anda/webhooks/buatqris` (dua URL
  didukung provider; boleh tambah satu cadangan).
- Email → **Email Accounts / SMTP**: OTP memakai `mail()`; di banyak shared
  hosting perlu SPF + From valid agar tidak masuk spam.

## 6. Cron

cPanel → **Cron Jobs** → tiap 5 menit:

```
php /home/username/public_html/cron/reconcile.php
```

## 7. Go-live checklist

`APP_DEBUG=false` · kredensial asli terisi · `provider_mode=production` ·
uji deposit kecil `test=1` dulu · webhook success+expired diuji ·
`install.php` dihapus dari server · backup database terjadwal ·
`storage/logs/` tidak dapat diakses browser (di luar `public_html` bila bisa,
atau lindungi dengan `.htaccess` tambahan).
