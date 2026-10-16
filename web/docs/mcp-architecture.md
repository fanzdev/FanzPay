# MCP Architecture — FanzPay

## 1. Keputusan transport
**Streamable HTTP JSON-RPC 2.0 pada `POST /mcp`** (cPanel-friendly, tanpa daemon/WebSocket).

- `POST /mcp` — satu-satunya endpoint protokol. Header `Content-Type: application/json`, `Accept: application/json`. Body ≤ 64 KB.
- Metode: `initialize`, `notifications/initialized` (no-op), `ping`, `tools/list`, `tools/call`. Error memakai kode JSON-RPC standar (`-32700`, `-32600`, `-32601`, `-32602`, `-32603`) + envelope MCP `{ "jsonrpc":"2.0", "id, "result" }`.
- `GET /mcp` — dokumen discovery manusia-mesin (nama server, versi protokol `2024-11-05`, daftar tool, endpoint auth). `GET /mcp/.well-known` tidak dipakai agar tidak bentrok dengan router mini.
- Alasan tidak memakai SSE server-push: hosting cPanel + PHP-FPM tidak mendukung koneksi gantung dengan baik; pola request/response sudah cukup untuk ChatGPT (Actions/connector), Claude (MCP connector), dan klien generik yang memanggil `tools/list` → `tools/call`.

## 2. Lapisan

```
Klien AI (ChatGPT/Claude/generik)
  │ HTTPS POST /mcp (JSON-RPC) + Bearer
  ▼
McpController::endpoint()            ← transport + rate-limit + audit
  │  McpAuth::fromRequest()         ← Bearer mcp_tokens (sha256) | sesi web | api_tokens(compat)
  ▼
McpServer::dispatch()                ← initialize / tools/list / tools/call + policy tool-enabled
  │  McpTools::definition()         ← 18 tool, skema ketat, scope per tool
  ▼
Service bisnis FanzPay (dipakai ulang, tidak diduplikasi):
  PricingService::catalog · WalletService · DepositService/MidtransService (read)
  NotificationService · McpPolicyService · McpPurchaseService (baru, tanpa PIN)
  │  PDO prepared + DB::tx + SELECT … FOR UPDATE + idempotency_keys
  ▼
MySQL (tabel lama + mcp_*)
```

## 3. Auth & otorisasi
- Token MCP: `mcp_tokens(id, user_id, name/client, token_hash sha256, scopes JSON [read|purchase], expires_at 90 hari, revoked_at, last_used_at)`. Token mentah (`fp_mcp_<32hex>`) hanya tampil sekali saat dibuat di halaman `/mcp-koneksi`.
- Scope: `read` (16 tool baca + prepare + policy-read) vs `purchase` (ditambah `execute_ppob_purchase`). Token tanpa scope purchase tidak bisa eksekusi walau kebijakan aktif.
- Identitas selalu dari token/sesi server — argumen `user_id` dari AI diabaikan. Setiap tool verifikasi `user_id = pemilik token` + `users.status=active`.
- Revokasi: halaman user `/mcp-koneksi` (cabut per token), Super Admin `/superadmin/mcp` (cabut token siapa pun), `revoked_at` diperiksa tiap request. Koneksi baru → notifikasi `mcp`; pencabutan → notifikasi `mcp`.
- Keterbatasan jujur: ini **Bearer token ber-scope** (seperti API Android yang ada), bukan OAuth 2.1 Authorization Code penuh — dipilih karena tidak ada server OAuth di repo dan cPanel tidak menyediakan layanan itu. Alur persetujuan meniru OAuth (halaman izin + token terikat user+scope+expiry) dan didokumentasikan di `mcp-threat-model.md`.

## 4. Kebijakan pembelian otomatis (opt-in, default OFF)
- Tabel `mcp_purchase_policy(user_id PK, enabled 0, max_per_tx, max_daily, allowed_categories JSON, allow_new_numbers, updated_at)` + batas sistem di `system_settings` (`mcp_*`, diatur Super Admin).
- Efektif = `min(batas user, batas sistem)`. Syarat eksekusi (semua harus lolos): enabled, scope purchase, kategori diizinkan, nominal ≤ max_per_tx, akumulasi harian sukses ≤ max_daily, saldo cukup, produk aktif + harga valid, nomor tujuan valid + aturan nomor baru, tidak duplikat (idempotency), tidak ada blokir risiko (akun aktif, rate-limit lolos).
- Bahasa alami tidak pernah mengubah batas. Perubahan batas sensitif hanya via halaman web resmi (CSRF + login; PIN tidak disimpan di konfigurasi).

## 5. Integritas transaksi
- `prepare_ppob_purchase` → tulis `mcp_preparations(id acak 24hex, user_id, product_code, customer_no, sell_price snapshot, expires 10 mnt, used_at)` — tanpa debit.
- `execute_ppob_purchase(preparation_id, idempotency_key)` → `McpPurchaseService::execute()`: lock policy+wallet, hitung ulang harga dari `PricingService`, cek batas, tulis `transactions(kind=ppob, status=processing)` + `provider_transactions` + `WalletService::append(debit)` + `idempotency_keys` dalam satu `DB::tx()`; panggil DigiFlazz (atau mock bila sandbox); `success → success`, `gagal → refund via ledger credit`, `tidak jelas/timeout → pending` (rekonsiliasi via cron/webhook, tidak auto-refund buta).
- Tidak ada endpoint MCP yang menulis saldo langsung — hanya service tervalidasi.

## 6. Data & tabel baru (selain tabel lama)
- `mcp_tokens`, `mcp_purchase_policy`, `mcp_preparations`, `mcp_support_tickets`, `mcp_reminders` (baca; tulis via halaman web), plus settings `mcp_*`. Lihat `database/migrate_mcp.sql`.
- Audit: reuse `audit_logs` (`action LIKE 'mcp.%'`) + `Logger::log('mcp', …)` yang sudah scrub secret. Tidak menyimpan prompt penuh, PIN, OTP, token.

## 7. Batas & hardening HTTP
- Body 64 KB, `tools/call` rate-limit 60/mnt per token + limit global Super Admin, output ledger dibatasi (maks 50 baris, string ≤ 2000 char), error generik ke klien + detail ke log. Wajib HTTPS di production; validasi `Origin/Host` untuk `POST /mcp` di browser.
