# Kontrak API internal FanzPay

Basis: `{APP_URL}`. Web memakai sesi cookie + CSRF; Android memakai
`Authorization: Bearer <token>` (dari `POST api/auth/login`, berlaku 30 hari).
Header idempotency untuk request finansial: `X-Idempotency-Key: <uuid>`.

Semua respons: `{"ok": true, "data": …}` atau
`{"ok": false, "message": "…pesan ramah…"}`. Kode HTTP: 200 / 401 / 404 / 422 / 429.

## Auth

- `POST /api/auth/login` `{email, password, device?}` → `{token, user{id,name,email,phone,role,public_id}}`
- `GET /api/me` → `{user, balance, has_pin, unread}`

## Dompet & transaksi (auth)

- `GET /api/wallet?type=&page=` → `{balance, ledger[{type,direction,amount,reference_id,created_at}], page}`
- `GET /api/transactions` → `[{reference_id,kind,amount,total,status,note,created_at}]` (30 terakhir)
- `GET /api/products?cat=` → `[{id,code,name,cat_code,cat_name,price}]`
  **tanpa** `base_price`/`margin` — harga modal tidak pernah keluar API.
- `GET /api/resolve?q=` → `{name, phone(masked), public_id}` | 404 bila tidak ada

## Finansial (auth + PIN bila perlu)

- `POST /api/transfer` `{lookup, amount, pin}` — gratis, atomic, idempoten
- `POST /api/deposit` `{amount}` → `{reference_id,status,total_amount,qr_url,payment_url,expired_at}`
- `GET /api/deposit/{ref}` → status (untuk polling hemat: ≥25 detik)
- `POST /api/withdraw` `{destination_id, amount, pin}`
- `GET /api/destinations`
- `POST /api/ppob` `{code, customer_no, pin}`
- `POST /api/pin` `{pin}` — atur/ganti PIN 6 digit
- `GET /api/notifications` → list + tandai dibaca

## Webhook (publik, HMAC wajib)

- `POST /webhooks/buatqris` — header `X-BuatQris-Event`, `X-BuatQris-Delivery`,
  `X-BuatQris-Signature: sha256=<hmac body mentah>`. Event:
  `payment.success|expired|failed`, `withdrawal.pending|approved|rejected`.
  Balas 2xx cepat; duplikat diabaikan via `webhook_events` + cek status.
- `POST /webhooks/digiflazz` — stub log; status PPOB disinkron via API + cron.
