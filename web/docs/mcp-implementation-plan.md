# Implementation Plan — MCP FanzPay (realitas repo)

## Fase 0 — Audit (selesai)
`docs/mcp-audit.md`. Blocker: tidak ada — MySQL lokal tersedia, PHP 8.2, pola Bearer + ledger + webhook sudah ada dan dipakai ulang.

## Fase 1 — Fondasi (DB + auth + protokol)
- `database/migrate_mcp.sql`: `mcp_tokens`, `mcp_purchase_policy`, `mcp_preparations`, `mcp_support_tickets`, `mcp_reminders` + seed `system_settings(mcp_*)`.
- `app/Mcp/McpAuth.php`: Bearer `mcp_tokens` (utama) → sesi web (fallback dashboard) → `api_tokens` (kompat, scope read).
- `app/Mcp/McpServer.php`: JSON-RPC 2.0 (`initialize`, `tools/list`, `tools/call`, `ping`), enforcement `mcp_enabled` + per-tool `mcp_tool_<nama>`.
- `app/Http/Controllers/McpController.php`: `POST /mcp`, `GET /mcp` (discovery), halaman user `/mcp-koneksi`, `/mcp-kebijakan`, `/panduan-mcp` (via WebController).
- Kriteria: `tools/list` tanpa auth mengembalikan daftar (tanpa data user); `tools/call` tanpa token → `-32603 auth required`.

## Fase 2 — 18 tools (semua baca dulu, pembelian terakhir)
1. `McpTools::definitions()` + `app/Services/McpPolicyService.php` (batas efektif user∩sistem).
2. Read: profile, balance (`WalletService`), history, tx-status, deposit-status (DB terverifikasi), search/compare/recommend/details (`PricingService::catalog` + validasi aktif), spending-summary, explain, help (basis statis dari FAQ `WebController::faq`), promotions (`promos` aktif), reminders (`mcp_reminders`).
3. Tulis terbatas: `prepare_ppob_purchase` (tanpa debit), `execute_ppob_purchase` via `app/Services/McpPurchaseService.php` (tanpa PIN, wajib policy), `get_purchase_policy`, `create_customer_support_ticket` (`mcp_support_tickets`, tanpa janji refund).
4. Kriteria: read-only tidak menulis ledger (diuji); prepare kedaluwarsa 10 mnt; execute idempoten.

## Fase 3 — Panel & halaman
- Super Admin `AdminController::mcp()` + `views/pages/admin/mcp.php`: on/off server, per-tool, batas sistem, rate-limit, daftar token aktif, cabut, audit `mcp.%`.
- User: `McpAccountController` (atau metode di `UserController`): buat/cabut token (tampil sekali), atur kebijakan (opt-in + risiko + CSRF), daftar persiapan/tiket/pengingat. Layout `app.php` yang ada.
- Landing: navbar `AI / MCP` → `/panduan-mcp`: setup nol→berhasil + tutorial ChatGPT, Claude, klien generik (SSE/HTTP) dengan contoh `curl`/JSON-RPC nyata.

## Fase 4 — Uji (`tests/mcp_run.php`, gaya `tests/run.php` tanpa framework)
Unit: skema, batas, harga, redaksi, idempotency-key. Integrasi (transaksi DB di-rollback): init, list, call read, prepare→execute mock, revokasi. Security: lintas-akun, scope, replay, PIN-ditolak, harga-manipulasi, tool-dimakan-admin. Failure: timeout provider (mock pending), callback duplikat. Regresi: `php tests/run.php` tetap hijau + login/deposit/PPOB web tidak berubah.

## Fase 5 — Deploy cPanel
1. PHP 8.1+, ekstensi `pdo_mysql, curl, mbstring, openssl`. 2. Import `migrate_mcp.sql`. 3. Pastikan HTTPS + `.env` tidak terbaca web. 4. Aktifkan di `/superadmin/mcp`. 5. Uji `POST /mcp` dari sandbox. Rollback: nonaktifkan `mcp_enabled=0` (endpoint → 503) + `DROP TABLE mcp_*` bila perlu (audit `audit_logs` dipertahankan).
