# Integrasi Midtrans Core API — FanzPay

## 1. Ringkasan
Deposit via **Midtrans Core API** (jalur utama): QRIS, Virtual Account
(BCA/BRI/BNI/Mandiri/Permata), e-wallet (GoPay/ShopeePay).
Jalur lama BuatQris dipertahankan (`deposit_gateway=buatqris`).

Alur: user buat deposit → backend `POST /v2/charge` (auth Basic Server Key,
`custom_expiry` 15 menit, `order_id` = referensi internal unik) → user bayar
→ Midtrans `POST /webhooks/midtrans` (verifikasi `signature_key`
SHA512) → cocok nominal → kredit saldo **satu kali** (guard `status=pending`
+ `SELECT ... FOR UPDATE` + unique ledger) → notifikasi dalam aplikasi.

Fee ditanggung pelanggan, dihitung dari konfigurasi admin
(persen untuk QRIS/e-wallet, flat untuk VA), ditampilkan sebelum bayar,
disimpan terpisah (`amount` / `fee` / `total_amount`), saldo bertambah
sebesar `amount`.

## 2. Konfigurasi Sandbox
1. Daftar di https://dashboard.sandbox.midtrans.com → Settings → Access Keys.
2. Isi salah satu (prioritas `.env`, fallback panel admin):
   - `.env`: `MIDTRANS_SERVER_KEY=SB-Mid-server-...`,
     `MIDTRANS_CLIENT_KEY=SB-Mid-client-...`, `MIDTRANS_MODE=sandbox`
   - atau panel Super Admin → **API Config** → bagian Midtrans.
3. Daftarkan Notification URL (pilih salah satu):
   - `https://domain/webhooks/midtrans`
   - `https://domain/webhooks/midtrans.php` (file langsung, cPanel-friendly)
4. Uji tanpa dana: **API Config → Uji konektivitas**.
   Key valid memberi respons 404-pada-order-fiktif = koneksi OK.
5. Simulasi bayar: dashboard Sandbox → Transactions → pilih order →
   **Simulate** (settlement/cancel/expire), atau gunakan deposit MOCK
   (tanpa Server Key) + tombol **Simulasi sukses** di halaman Deposit admin.

## 3. Migrasi database
Server yang sudah punya tabel lama:
```
php web/database/migrate_midtrans.php
```
Idempoten (aman diulang). Menambah kolom
`gateway, payment_type, payment_code, qr_string, actions, raw_response, credited_at`
pada `deposits` + indeks + seed pengaturan. Rollback: kolom tambahan boleh
`DROP` setelah memastikan `gateway` tidak dipakai (jalur buatqris tidak
membacanya — aman).

Instalasi baru: `install.php` sudah memuat seed yang sama.

## 4. Rekonsiliasi & cron (cPanel)
```
php /home/USER/public_html/web/cron/reconcile_midtrans.php   # tiap 5 menit
```
Menyinkronkan deposit pending via **Status API resmi** (`GET /v2/{order}/status`),
menandai kedaluwarsa lokal, TIDAK PERNAH kredit ulang tanpa status sah.
Panel Super Admin → **Rekonsiliasi**: daftar Midtrans pending + tombol
**Cek ke Midtrans** per order + deteksi selisih saldo-vs-ledger.

## 5. Beralih ke Production
1. Ambil Server/Client Key production (dashboard production).
2. Ganti `MIDTRANS_MODE=production` + Notification URL HTTPS production.
3. Uji konektivitas → satu deposit kecil nyata → verifikasi webhook
   (`webhook_events` status `processed`) → cek ledger.
4. Jangan aktifkan production otomatis — perlu persetujuan Super Admin.

## 6. Batasan yang diketahui
- **Payout otomatis TIDAK tersedia** via Core API (produk pencairan Midtrans
  terpisah: Payouts, butuh aktivasi & akun tersendiri). Penarikan di FanzPay
  tetap alur **manual tercatat** (`withdrawals` + reversal ledger + audit).
  Abstraksi provider sudah dipisah (`WithdrawService` vs `MidtransService`)
  sehingga Payouts dapat disambung tanpa menyentuh deposit.
- Metode aktif mengikuti akun Midtrans masing-masing (mis. bank tertentu /
  e-wallet tertentu bisa nonaktif di sisi Midtrans → charge ditolak eksplisit
  dengan pesan ramah, deposit lokal ditandai `failed` dan user boleh coba lagi).
- OVO/DANA/LinkAja/gerai retail belum dikatalogkan — tambah via
  `MidtransService::METHODS` + payload sesuai dokumen terbaru.
- Countdown di browser hanya tampilan; status final selalu dari backend.

## 7. Keamanan
Server Key hanya backend (`.env` / `system_settings`), tidak ke browser/log
(`Logger::scrub` + masking admin). Webhook: validasi format → verifikasi
SHA512 → cocok order → cocok nominal → cegah ganda → respons 200/400/401
yang benar. Rate limit: deposit 10/jam/user, webhook 120/mnt/IP, API deposit
10/jam. Semua nominal integer rupiah, prepared statements, CSRF, otorisasi
super-admin untuk kunci & simulasi.
