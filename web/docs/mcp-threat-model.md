# Threat Model — MCP FanzPay (STRIDE-lite)

## Aset
Saldo (`wallets`/`wallet_ledger`), transaksi, deposit Midtrans, katalog+harga, PIN (`transaction_pins`), token (`mcp_tokens`/`api_tokens`), sesi web, kredensial provider (DigiFlazz/Midtrans), `audit_logs`.

## Ancaman & mitigasi (terkait kode nyata)
1. **Spoofing identitas (user_id palsu dari AI)** — Mitigasi: identitas hanya dari `McpAuth::fromRequest()` (token sha256 + expiry + revoked + user aktif); argumen AI tidak dipercaya. Diuji: `cross-account`.
2. **Token bocor / replay** — Token 256-bit (`random_bytes(32)`), simpan sha256, tampil sekali, expiry 90 hari, rotasi manual via halaman, revokasi user+admin, `last_used_at` untuk deteksi anomali. Tidak disimpan di localStorage tutorial (instruksi: simpan di password manager / secret konektor).
3. **Prompt injection → ubah kebijakan/eksekusi** — Input AI = data tidak tepercaya: validasi tipe/panjang/whitelist di server + JSON Schema; deskripsi produk tidak dieksekusi; batas hanya berubah via halaman web CSRF; `execute` wajib `preparation_id` terikat user + belum dipakai + belum kedaluwarsa.
4. **PIN/OTP via AI** — Skema tool tidak memiliki field PIN/OTP; bila ditemukan pola PIN di argumen → tolak + audit `mcp.pin_blocked`. PIN hanya via form web resmi (`PinService`).
5. **Manipulasi harga** — Harga dihitung ulang server dari `product_prices`/`PricingService`; snapshot prepare hanya untuk tampilan; selisih harga → tolak dengan pesan jelas (anti race).
6. **Double-spend / race** — `DB::tx` + `SELECT … FOR UPDATE` (wallet+policy) + `idempotency_keys` + UNIQUE ledger; retry tanpa idempotency key baru → kembalikan transaksi lama bila `preparation` sama.
7. **SSRF/URL arbitrer** — Tidak ada tool yang menerima URL/callback; satu-satunya HTTP keluar adalah DigiFlazz/Midtrans resmi (URL dari `.env`, bukan dari AI).
8. **SQLi/XSS** — Seluruh query PDO prepared; output di-escape di view; error MCP generik (`Transaksi sedang diproses`), detail ke `storage/logs` yang sudah scrub.
9. **DoS / brute-force** — `RateLimiter` DB: login MCP 10/5 mnt, `tools/call` 60/mnt/token, prepare 20/jam, execute 10/10 mnt; body 64 KB; pagination maks 50.
10. **Kebocoran modal/margin** — `catalog()` dan tool hanya kembalikan `sell_price`; `base_price/margin` tidak pernah diserialisasi (diuji `no-cost-leak`).
11. **Callback palsu** — Status deposit hanya dari DB terverifikasi + webhook signature yang ada; MCP tidak menulis status deposit.
12. **Residual risk (diterima & didokumentasikan)**: bukan OAuth 2.1 penuh; klien AI yang menyimpan token dengan buruk di luar kendali FanzPay (mitigasi: revokasi + expiry + notifikasi); mock sandbox bisa membingungkan (mitigasi: label `is_mock` + status pending jujur).

## Batasan non-ancaman (dilarang oleh scope)
Tidak ada tool transfer antar-pelanggan, withdraw, admin, ubah saldo langsung, atau SQL bebas — permintaan semacam itu ditolak dengan `method not found / permission denied` dan diarahkan ke aplikasi resmi.
