# FanzPay Android

Aplikasi Android native (Kotlin, Jetpack Compose Material3) untuk pengguna.
Backend: `web/api/*` di project ini.

## Persyaratan

- Android Studio Hedgehog atau lebih baru
- JDK 17 (bawaan Android Studio), Android SDK 34, minSdk 26
- Backend web berjalan & dapat dijangkau dari HP/emulator

## Konfigurasi server

| Build | Lokasi | Nilai default |
|---|---|---|
| debug | `app/build.gradle.kts` → `API_BASE_URL` | `http://10.0.2.2/fanzpay/web/` (emulator → XAMPP host) |
| HP fisik | layar Login → field **Server** | mis. `https://fanzpay.domainanda/` (disimpan permanen) |

> Gunakan `http://IP-LAN-PC/fanzpay/web/` bila HP fisik + XAMPP satu Wi-Fi
> (contoh `http://192.168.1.10/fanzpay/web/`). Production wajib HTTPS.

## Build

1. Android Studio → **Open** → pilih folder `android/`.
2. Tunggu sync Gradle selesai.
3. **Run ▶** ke emulator/HP (debug), atau **Build → Generate Signed Bundle/APK**
   untuk APK release (ProGuard aktif, model API di-keep).

## Peta fitur ↔ API

| Layar | Endpoint |
|---|---|
| Login | `POST api/auth/login` → Bearer disimpan di DataStore |
| Beranda | `GET api/me`, `GET api/transactions` |
| Produk + beli | `GET api/products`, `POST api/ppob` (PIN) |
| Transfer | `GET api/resolve`, `POST api/transfer` (PIN, idempotency header otomatis) |
| Deposit | `POST api/deposit`, `GET api/deposit/{ref}` (polling 25 dtk) |
| Withdraw | `GET api/destinations`, `POST api/withdraw` |
| Riwayat | `GET api/transactions` (+ `api/wallet` untuk ledger) |
| Notifikasi | `GET api/notifications` |
| QR saya | generate offline (ZXing) dari `public_id` |
| Scan | CameraX + ML Kit → deep link `fanzpay://pay/u/{id}` → Transfer terisi |
| Profil | `POST api/pin`, logout hapus token |

## Keamanan sisi klien

- Tanpa secret provider di APK; semua kredensial di backend.
- Token sesi di DataStore (bukan log, bukan backup plaintext yang dibaca app lain).
- PIN tidak disimpan — hanya dikirim per transaksi via HTTPS.
- `usesCleartextTraffic=true` hanya untuk kemudahan dev XAMPP; untuk release
  production gunakan HTTPS dan set flag tersebut ke `false`.
