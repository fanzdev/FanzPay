# MCP Audit — FanzPay (kondisi nyata repository)

Tanggal: 2026-10-16. Sumber: inspeksi langsung `web/` (PHP 8.2 CLI, MySQL via PDO).

## 1. Struktur & entry point
- Front controller: `web/index.php` — autoload `App\` → `web/app/`, `Session::start()`, security headers, maintenance gate, `Router` mini (`app/Core/Router.php`, hanya GET/POST/any + `{param}`), ~70 rute, render via `app/Core/View.php` (`views/pages/<nama>.php` + `views/layouts/*.php`: `public`, `auth`, `app`, `admin`).
- Controller: `AuthController` (login/register/OTP/setup-super), `WebController` (landing + SEO), `UserController` (guard `role=user`), `AdminController` (guard `isAdmin` + `need(permission)`), `ApiController` (session ATAU Bearer `api_tokens`, khusus Android role user), `WebhookController` (buatqris/digiflazz/midtrans).
- Proteksi path internal di `index.php` + `.htaccess`: `/.env`, `/config/`, `/storage/`, `/tests/`, `/database/`, `/app/` → 404.

## 2. Versi & hosting
- PHP 8.2.12 (CLI XAMPP). Kode `declare(strict_types=1)`, sintaks PHP 8 (named args tidak dipakai, `str_contains/str_starts_with` dipakai → butuh PHP ≥ 8.0; cPanel wajib PHP 8.1+).
- Ekstensi yang dipakai kode: `pdo_mysql`, `curl` (DigiFlazz/Midtrans client), `mbstring` implisit, `openssl/random_bytes`, `password_hash`. `allow_url_fopen` tidak dibutuhkan.
- cPanel: arsitektur ini kompatibel — tidak butuh WebSocket/daemon. MCP diimplementasikan sebagai **JSON-RPC 2.0 via POST HTTPS** (`/mcp`) memakai autoload + PDO yang sama. Tidak perlu Node.js/Redis.
- Config: `web/config/config.php` baca `web/.env` (tidak di-commit; `.env.example` tersedia). Secret hanya server-side: `buatqris.*`, `digiflazz.*`, `midtrans.server_key`. `Logger::scrub()` meredaksi password/pin/secret/api_key/token/server_key/client_key/otp.

## 3. Auth, sesi, PIN, role
- Sesi: `app/Core/Session.php` — cookie persisten 30 hari (`LIFETIME=2592000`, `HttpOnly`, `SameSite=Lax`, `Secure` bila HTTPS), `gc_maxlifetime` disinkronkan, `session_regenerate_id` berkala. Tabel `user_sessions` (30 hari).
- Remember-me: `app/Core/Auth.php` — cookie `fp_remember` (`user_id:token64hex`), di DB hanya `sha256` di `remember_tokens`, rotasi tiap dipakai, max 10 perangkat, hapus saat logout. `Auth::user()` = sesi → remember-cookie → null.
- API token Android: `api_tokens(token_hash sha256, device, expires_at 30 hari)` via `Auth::userFromBearer()`. Pola yang sama dipakai ulang untuk token MCP (tabel terpisah + scope).
- Role: `users.role ∈ {super_admin, admin, user}`. Permission granular `admin_permissions` + `Auth::PERMISSIONS` (17 kode). PIN: `transaction_pins(pin_hash bcrypt, failed_attempts, locked_until)` — `PinService::verify()` rate-limit 5/5 mnt (`rate_limits`), kunci 15 mnt setelah 5 gagal. CSRF per-sesi (`app/Core/CSRF.php`).
- Temuan: `AuthController::login()` sudah redirect berbasis role; `ApiController::needUser()` menolak admin di Android. Tidak ada OAuth — MCP memakai Bearer token + halaman persetujuan web (didokumentasikan sebagai keterbatasan vs OAuth 2.1 penuh).

## 4. Skema data (aktual, `database/schema.sql`)
- `users`, `wallets(user_id PK, balance INT, locked)`, `wallet_ledger(transaction_id UNIQUE per user+direction, reference_id, type, direction, amount, balance_before/after, source/destination/status)` — append-only.
- `transactions(reference_id UNIQUE, user_id, kind, amount/fee/total INT, status pending|processing|success|failed|expired|refunded|reversed, idempotency_key)`, `transaction_items`, `provider_transactions(base_price MODAL privat, sell_price, status, request/response JSON)`.
- `products(code SKU UNIQUE, is_active)`, `product_categories`, `providers`, `provider_products(provider_id, provider_sku UNIQUE, base_price MODAL, raw JSON)`, `product_prices(sell_price)`, `pricing_rules(scope category|product, margin)`.
- `deposits(ref UNIQUE, amount/total/fee/credit_amount, status, qr_url/payment_url, expired_at, paid_at, is_mock)`, `withdrawal_destinations`, `withdrawals`, `internal_transfers`, `notifications`, `audit_logs(actor, action, target, result, metadata JSON — scrubbed)`, `security_events`, `webhook_events(provider, delivery_id, event UNIQUE, status)`, `system_settings(key PK)`, `rate_limits`, `promos`, `banners`, `email_otps`, `idempotency_keys`.

## 5. Integrasi provider
- DigiFlazz: `app/Providers/DigiFlazzClient.php` — sign `md5(user+key+refId)`, endpoint `/cek-saldo`, `/price-list`, `/transaksi` (prepaid/pasca), timeout 25 dtk. Bila `sandbox` atau kredensial kosong → `MockProvider` (label `is_mock=true`, status Pending — tidak pernah dianggap sukses nyata).
- Midtrans: `app/Services/MidtransService.php` + `app/Providers/MidtransClient.php` — metode `qris/bca/bri/bni/mandiri/permata/gopay/shopeepay`, fee transparan (`feeFor/quote`), `enabledMethods()` dari settings. Webhook diverifikasi signature (periksa `WebhookController::midtrans` + `MidtransService::applyNotification`).
- BuatQris legacy dipertahankan (`DepositService::createLegacyBuatQris`, `BuatQrisClient::verifySignature` HMAC-SHA256 `sha256=`).

## 6. Harga, saldo, status
- Harga jual: `PricingService::compute()` = `base_price + margin` (override produk → rule kategori → `default_margin` 1000); katalog publik `PricingService::catalog()` hanya kembalikan `sell_price AS price` — modal/margin tidak pernah ke browser/API/MCP.
- Saldo: hanya via `WalletService::append()` di dalam `DB::tx()` + `SELECT ... FOR UPDATE`; `balance()` dari kolom `wallets.balance`; `rebuild()` dari ledger untuk rekonsiliasi. Semua nominal INT rupiah.
- Status transaksi: `pending → processing → success | failed | expired | refunded | reversed`. Webhook idempoten via `webhook_events` UNIQUE + cek status terminal sebelum update.

## 7. Webhook & proteksi
- `WebhookController`: buatqris (HMAC), digiflazz, midtrans (signature). Pola `applyWebhook/applyNotification`: dedup `delivery_id`, abaikan bila status terminal, tulis dalam transaksi DB, kirim notifikasi hanya dari status terverifikasi.

## 8. Transaksi DB, locking, idempotency
- Ya: `DB::tx()` di `PpobService::purchase`, `DepositService`, `TransferService`, `WithdrawService`; `WalletService::append` lock baris wallet; `UNIQUE(transaction_id,user_id,direction)` cegah double-ledger; `idempotency_keys(key PK)` untuk retry aman. Pola yang sama dipakai untuk `execute_ppob_purchase` MCP.

## 9. Rahasia
- Semua secret di `web/.env` + override non-rahasia via `system_settings` (panel admin). Tidak ada secret di JS/view. `mask_secret()` untuk tampilan admin. Dokumentasi tidak mencantumkan nilai nyata.

## 10. Bug/risiko yang dicatat (ditangani di implementasi MCP)
1. `PpobService::purchase` mensyaratkan PIN sebagai argumen — MCP **dilarang** menerima PIN, sehingga dibuat `McpPurchaseService` terpisah (kebijakan opt-in + batas) tanpa jalur PIN; tidak mengubah alur web/Android.
2. `DELETE ... ORDER BY ... LIMIT 1` (MySQL-spesifik) hanya dipakai di remember-token; untuk MCP dipakai `DELETE` + `SELECT` biasa agar portabel MariaDB.
3. `Router` tidak punya PUT/PATCH/DELETE — MCP cukup POST/GET.
4. Tidak ada kolom `scope` di `api_tokens` — dibuat tabel `mcp_tokens` tersendiri dengan scope agar tidak merusak token Android.
5. `products.code` vs `provider_sku` ganda — tool katalog selalu validasi `products.is_active=1` + `sell_price` ada sebelum ditawarkan.
6. Mock provider berstatus Pending — tool MCP tidak pernah melaporkan mock sebagai sukses; UI/tutorial menulis "MODE SANDBOX" bila `provider_mode != production`.
