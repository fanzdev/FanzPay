Anda adalah Senior Full-Stack Engineer, Software Architect, UI/UX Designer, Security Engineer, dan Fintech/PPOB System Architect.

Bangun aplikasi web production-ready bernama:

FANZPAY

FanzPay adalah platform PPOB dan digital wallet modern yang menyediakan pembelian berbagai produk PPOB, deposit, withdrawal, serta transfer uang gratis antar pengguna FanzPay.

Jangan membuat prototype, mockup kosong, atau sekadar landing page.

Bangun fondasi aplikasi yang benar-benar siap dikembangkan menjadi bisnis production.

==================================================
1. BRAND & VISUAL IDENTITY
==================================================

Gunakan logo FanzPay yang diberikan sebagai sumber utama identitas visual.

JANGAN mengubah bentuk, proporsi, warna, atau filosofi logo.

Gunakan karakter visual yang berasal dari logo:
- Electric blue
- Bright/light blue
- Dark navy/black sebagai warna pendukung
- White/neutral surface

Style:
- Premium fintech
- Modern
- Minimal
- Elegant
- Clean
- Sophisticated
- Glass UI secara terkontrol
- Subtle gradients
- Soft shadows
- Fine borders
- Smooth micro-interactions

HINDARI:
- AI-slop design
- Terlalu banyak gradient
- Terlalu banyak glass effect
- Neon berlebihan
- Dashboard yang terlihat seperti template AI
- Card berlebihan
- Typography yang tidak konsisten
- Animasi berlebihan
- Layout yang terlalu ramai

Desain harus terasa seperti produk fintech yang dibuat oleh designer dan engineer profesional.

Gunakan prinsip:
"Simple interface, sophisticated system."

==================================================
2. RESPONSIVE DESIGN
==================================================

FanzPay harus benar-benar responsive.

BREAKPOINT utama:
- Mobile
- Tablet
- Desktop
- Large desktop

MOBILE:
Buat UI terasa seperti aplikasi Android modern.

Karakter:
- Bottom navigation
- Touch-friendly controls
- Large tap targets
- Mobile-first layout
- Sticky action ketika diperlukan
- Gesture-friendly interaction
- Compact cards
- Native-app-like spacing
- Smooth transitions
- Bottom sheets
- Modal yang terasa seperti Android
- Skeleton loading
- Pull-to-refresh style jika relevan
- Tidak terasa seperti website desktop yang diperkecil

Desktop:
Gunakan dashboard profesional:
- Sidebar navigation
- Topbar
- Data visualization
- Tables
- Statistics
- Management panels
- Elegant cards
- Responsive content area

Tablet:
Gunakan layout hybrid.

Pastikan tidak ada:
- horizontal overflow
- teks terpotong
- button keluar layar
- tabel rusak
- modal terlalu besar
- sidebar bertabrakan dengan konten

==================================================
3. TECHNOLOGY STACK
==================================================

BACKEND:
PHP Native / PHP modern tanpa framework backend berat.

DATABASE:
MySQL / MariaDB.

LOCAL DEVELOPMENT:
XAMPP.

PRODUCTION:
cPanel shared hosting.

Frontend bebas menggunakan teknologi modern selama:
- mudah di-build
- hasil akhirnya dapat di-deploy ke cPanel
- tidak membutuhkan Node.js server di production
- backend tetap PHP Native
- asset frontend dapat berupa static build
- deployment sederhana

Gunakan JavaScript modern secara terstruktur.

Gunakan CSS architecture yang maintainable.

Jangan membuat frontend yang bergantung pada development server ketika production.

==================================================
4. ARCHITECTURE
==================================================

Gunakan architecture yang rapi dan maintainable.

Pisahkan:

- Presentation layer
- Routing
- Controllers
- Services
- Repositories / Data Access
- Models
- Authentication
- Authorization
- Validation
- API integrations
- Payment integrations
- Wallet/Ledger
- Notifications
- Logging
- Security
- Configuration

Jangan membuat seluruh aplikasi dalam satu file PHP.

Jangan membuat:
index.php 5000 baris
admin.php 4000 baris
atau file monolithic lainnya.

Gunakan modular architecture.

Credential rahasia harus berada di environment/configuration yang aman.

JANGAN pernah menaruh:
- API key
- secret token
- database password
- webhook secret
di frontend.

==================================================
5. USER ROLES
==================================================

HANYA ada 3 role:

1. SUPER ADMIN
2. ADMIN
3. USER / PELANGGAN

Tidak boleh membuat role tambahan.

==================================================
6. SUPER ADMIN
==================================================

SUPER ADMIN adalah pemilik dan pengendali penuh sistem.

Super Admin memiliki FULL CONTROL tanpa pengecualian terhadap:

- User
- Admin
- Role permission Admin
- Produk PPOB
- Kategori produk
- Harga jual
- Margin
- Wallet
- Deposit
- Withdrawal
- Transfer
- Transaksi
- Refund
- Failed transaction
- Pending transaction
- Provider
- DigiFlazz
- BuatQris
- API configuration
- Webhook
- Notification
- Promo
- Banner
- Maintenance
- System configuration
- Security configuration
- Rate limit
- Transaction limit
- Minimum deposit
- Maximum deposit
- Minimum withdrawal
- Maximum withdrawal
- Daily transaction limit
- User limits
- Admin limits
- Audit logs
- Financial reports
- Transaction reports
- User reports
- Provider balance
- Provider status
- System health
- Database-related management tools
- Backup configuration
- System logs

Super Admin harus mempunyai dashboard bisnis yang sangat lengkap.

==================================================
7. ADMIN
==================================================

Admin TIDAK memiliki akses otomatis terhadap seluruh sistem.

Super Admin menentukan permission Admin.

Buat permission system granular.

Contoh:

- dashboard.view
- users.view
- users.edit
- transactions.view
- transactions.manage
- deposits.view
- withdrawals.view
- withdrawals.manage
- products.view
- products.manage
- reports.view
- notifications.manage
- etc.

Super Admin dapat:
- membuat Admin
- menghapus Admin
- mengaktifkan/nonaktifkan Admin
- menentukan permission Admin
- mengubah permission kapan saja

Admin hanya dapat mengakses fitur yang diberikan.

==================================================
8. USER / CUSTOMER
==================================================

User dapat:

- Register
- Login
- Logout
- Verify email
- Manage profile
- Manage PIN
- Deposit
- Withdraw
- Transfer antar user
- Scan QR user
- Receive money
- Purchase PPOB
- View transaction history
- View wallet history
- Save favorite products
- Receive notifications
- View notification history
- Change account information
- Manage saved withdrawal destinations

==================================================
9. AUTHENTICATION
==================================================

Registrasi menggunakan:

- Nama
- Nomor HP
- Email
- Password
- OTP email

Email adalah sumber OTP.

Gunakan:
- secure password hashing
- password_hash()
- password_verify()
- session security
- session regeneration
- session expiration
- logout invalidation
- brute-force protection
- rate limiting

Jangan pernah menyimpan password plaintext.

==================================================
10. TRANSACTION PIN
==================================================

User menggunakan PIN transaksi.

PIN:
- 6 digit
- tidak disimpan plaintext
- hash PIN
- rate limit percobaan
- temporary lock setelah beberapa kesalahan
- audit setiap penggunaan PIN

PIN digunakan untuk:
- transfer
- withdrawal
- pembelian PPOB
- aktivitas finansial sensitif lainnya

Jangan menggunakan password sebagai pengganti PIN.

==================================================
11. WALLET SYSTEM
==================================================

FanzPay harus memiliki internal wallet.

Jangan hanya menyimpan saldo sebagai angka sederhana.

Gunakan ledger transaksi yang immutable / append-only.

Setiap perubahan saldo harus mempunyai:

- transaction ID
- reference ID
- user ID
- type
- direction
- amount
- balance before
- balance after
- source
- destination
- status
- timestamp
- metadata
- related provider transaction ID

Gunakan database transaction dan row locking ketika memproses saldo.

Cegah:
- double spending
- race condition
- duplicate transaction
- replay transaction
- double webhook
- double refund

Saldo harus dapat direkonstruksi dari ledger.

==================================================
12. DEPOSIT
==================================================

Gunakan BuatQris untuk deposit.

Flow:

User
→ Deposit
→ Input nominal
→ Backend membuat QRIS
→ QR ditampilkan
→ User melakukan pembayaran
→ BuatQris mengirim webhook
→ Backend memverifikasi signature
→ transaksi diproses secara idempotent
→ saldo user bertambah
→ ledger dibuat
→ notification dikirim
→ transaksi menjadi SUCCESS

Status:
- PENDING
- SUCCESS
- EXPIRED
- FAILED

Jangan percaya status dari frontend.

Frontend hanya menampilkan status.

Backend adalah sumber kebenaran.

==================================================
13. BUATQRIS INTEGRATION
==================================================

Integrasikan BuatQris menggunakan server-side API.

Gunakan dokumentasi resmi BuatQris terbaru.

API credential:
- account_id
- secret_token
- webhook signing secret

Simpan di environment/configuration aman.

Jangan pernah expose credential ke:
- browser
- JavaScript
- mobile app
- HTML
- API response

Gunakan webhook verification menggunakan HMAC-SHA256.

Verifikasi:

X-BuatQris-Signature

Pastikan webhook:
- authenticated
- validated
- idempotent
- logged
- replay-safe

Gunakan event yang tersedia:
- payment.success
- payment.expired
- payment.failed
- withdrawal.pending
- withdrawal.approved
- withdrawal.rejected

Jika webhook datang dua kali, transaksi tidak boleh diproses dua kali.

==================================================
14. WITHDRAWAL
==================================================

Gunakan arsitektur withdrawal berikut:

USER
→ tambah rekening/e-wallet withdrawal
→ simpan destination
→ validasi
→ user memilih destination
→ input nominal
→ validasi limit
→ konfirmasi
→ PIN
→ lock saldo
→ create withdrawal request
→ backend memanggil BuatQris
→ status PENDING
→ webhook provider
→ APPROVED / SUCCESS
→ finalize ledger

Jika withdrawal REJECTED / FAILED:

→ jangan kehilangan saldo user
→ rollback/release locked balance
→ buat reversal ledger
→ notification user

Jangan mengurangi saldo dua kali.

Jangan percaya nominal dari frontend.

Fee withdrawal harus mengikuti response/provider configuration.

Jangan menghitung fee provider secara manual jika provider memberikan nilai final.

==================================================
15. TRANSFER USER-TO-USER
==================================================

Fitur utama FanzPay:

TRANSFER GRATIS ANTAR USER.

User dapat mengirim uang menggunakan:

1. Nomor HP
2. Scan QR FanzPay

Flow:

Sender
→ input nomor HP / scan QR
→ backend mencari recipient
→ tampilkan nama recipient
→ sender memasukkan nominal
→ validasi saldo
→ PIN
→ database transaction
→ debit sender
→ credit recipient
→ ledger sender
→ ledger recipient
→ notification kedua user
→ transaction SUCCESS

Biaya:
Rp0.

Jangan menggunakan payment gateway untuk transfer internal.

Transfer harus atomic.

Jika salah satu proses gagal:
SEMUA perubahan harus rollback.

==================================================
16. QR USER
==================================================

Setiap user mempunyai QR FanzPay.

QR harus mengidentifikasi user secara aman.

Jangan memasukkan data sensitif langsung ke QR.

Gunakan public identifier / opaque token.

Contoh:

fanzpay://pay/u/{public_id}

atau URL aman:

https://domain.com/pay/{public_id}

Jangan expose:
- database ID sensitif
- email
- password
- PIN
- balance

==================================================
17. DIGIFLAZZ
==================================================

Integrasikan DigiFlazz sebagai provider utama produk PPOB.

Gunakan API resmi DigiFlazz.

Jangan hard-code produk.

Ambil dan sinkronisasi katalog produk dari provider.

Kategori produk dapat meliputi:

- Pulsa
- Paket Data
- Token PLN
- PLN Pascabayar
- E-Money
- Voucher Game
- TV
- PDAM
- BPJS
- dan produk lain yang tersedia pada DigiFlazz

Sistem harus dibuat extensible sehingga produk baru dari provider dapat masuk tanpa redesign database.

==================================================
18. PRODUCT PRICING
==================================================

Harga internal provider:
PRIVATE.

User hanya melihat:

HARGA JUAL.

Formula internal:

harga jual =
harga dasar provider + margin FanzPay

Jangan pernah tampilkan:
- harga modal
- margin
- profit
- harga provider

kepada User.

Contoh UI:

Pulsa Telkomsel 10K
Rp12.500

JANGAN:

Harga provider Rp11.800
Margin Rp700
Harga jual Rp12.500

Informasi margin hanya dapat dilihat oleh Super Admin.

==================================================
19. DYNAMIC PRICING
==================================================

Super Admin dapat menentukan:

- margin per produk
- margin per kategori
- harga jual
- minimum margin
- maximum margin
- harga khusus jika diperlukan

Pastikan pricing engine terpisah dari product catalog.

==================================================
20. PPOB TRANSACTION FLOW
==================================================

Contoh:

User
→ pilih produk
→ masukkan nomor
→ backend validasi
→ tampilkan harga jual
→ user konfirmasi
→ PIN
→ lock saldo
→ create internal transaction
→ call DigiFlazz
→ receive response
→ update transaction
→ update ledger
→ notification

Status harus mendukung:

- PENDING
- PROCESSING
- SUCCESS
- FAILED
- REFUNDED
- REVERSED

Gunakan unique reference ID.

Jangan pernah menggunakan timestamp saja sebagai reference ID.

==================================================
21. IDEMPOTENCY
==================================================

WAJIB.

Setiap transaksi finansial harus memiliki idempotency protection.

Contoh:

Jika request pembelian dikirim dua kali karena:
- double click
- network retry
- browser refresh
- webhook retry

maka transaksi hanya diproses SATU KALI.

==================================================
22. PENDING TRANSACTION
==================================================

Jika DigiFlazz memberikan status Pending:

JANGAN langsung refund.

Simpan status PENDING.

Gunakan:
- webhook jika tersedia
- status check API jika diperlukan
- reconciliation mechanism

Jika provider akhirnya gagal:
refund.

Jika sukses:
finalize transaction.

Jangan membuat asumsi berdasarkan timeout HTTP.

==================================================
23. RECONCILIATION
==================================================

Buat sistem reconciliation.

Super Admin dapat melihat transaksi:

- pending terlalu lama
- provider mismatch
- balance mismatch
- webhook missing
- duplicate callback
- uncertain transaction

Sediakan mekanisme:

CHECK PROVIDER STATUS

sehingga transaksi dapat disinkronkan kembali dengan provider.

==================================================
24. SECURITY
==================================================

Gunakan defense-in-depth security architecture.

Implement:

- PDO prepared statements
- CSRF protection
- XSS protection
- SQL injection protection
- input validation
- output escaping
- secure cookies
- HttpOnly
- Secure cookie
- SameSite
- session regeneration
- rate limiting
- brute force protection
- login throttling
- PIN throttling
- authorization middleware
- RBAC
- IDOR protection
- request validation
- API authentication
- webhook signature verification
- replay protection
- idempotency
- audit logging
- security headers
- CSP jika memungkinkan
- HSTS production
- HTTPS
- secret management
- least privilege
- database constraints
- transaction locking

Jangan mengklaim aplikasi "tidak bisa diretas".

Target:
minimize attack surface
+ prevent common attacks
+ detect attacks
+ contain damage
+ maintain auditability.

==================================================
25. AUDIT LOG
==================================================

Semua aktivitas sensitif harus tercatat.

Contoh:

- login
- failed login
- logout
- password change
- PIN change
- transfer
- deposit
- withdrawal
- PPOB purchase
- admin action
- permission change
- pricing change
- API configuration change
- system configuration change

Audit log minimal:

- actor
- actor role
- action
- target
- target ID
- IP
- user agent
- timestamp
- metadata
- result

Audit log tidak boleh mudah dihapus oleh Admin.

Super Admin memiliki akses penuh.

==================================================
26. NOTIFICATION SYSTEM
==================================================

Gunakan:

1. In-app notification
2. Push notification

Notification events:

- deposit success
- deposit failed
- withdrawal requested
- withdrawal approved
- withdrawal rejected
- transfer received
- transfer sent
- PPOB success
- PPOB failed
- refund
- security event
- announcement

Buat notification center.

Unread counter.

Mark as read.

Mark all as read.

==================================================
27. SUPER ADMIN DASHBOARD
==================================================

Dashboard harus terasa seperti command center bisnis.

Tampilkan:

- total users
- active users
- total transactions
- transaction volume
- revenue
- estimated profit
- deposit volume
- withdrawal volume
- transfer volume
- PPOB volume
- successful transactions
- failed transactions
- pending transactions
- provider balance
- wallet liability
- system status

Charts:
- daily transactions
- revenue
- deposit
- withdrawal
- user growth
- product performance

Sediakan date filter.

==================================================
28. USER DASHBOARD
==================================================

Mobile-first.

Tampilkan:

Greeting

Saldo:
Rp xxx.xxx

Quick actions:

Deposit
Transfer
Withdraw
Riwayat

Produk populer:

Pulsa
Data
PLN
E-Wallet
Game
etc.

Recent transactions.

Notification.

QR saya.

UI harus sederhana.

Jangan memenuhi layar dengan card yang tidak penting.

==================================================
29. USER WALLET
==================================================

User dapat melihat:

- current balance
- available balance
- pending balance jika digunakan
- transaction history
- deposit
- withdrawal
- transfer
- PPOB
- refund

Gunakan format rupiah Indonesia.

Contoh:

Rp 125.000

Bukan:

125000 IDR

==================================================
30. TRANSACTION HISTORY
==================================================

Filter:

- Semua
- Deposit
- PPOB
- Transfer
- Withdrawal
- Refund

Filter tanggal.

Search.

Status badge.

Detail transaksi.

Reference ID.

Provider reference ID jika relevan.

==================================================
31. ADMIN MANAGEMENT
==================================================

Super Admin dapat:

Create Admin
Edit Admin
Suspend Admin
Activate Admin
Reset Admin
Set permissions

Permission matrix harus mudah digunakan.

==================================================
32. SYSTEM SETTINGS
==================================================

Super Admin dapat mengatur:

- site name
- logo
- favicon
- maintenance mode
- transaction limits
- withdrawal limits
- deposit limits
- transfer limits
- PIN settings
- security settings
- notification settings
- pricing settings
- provider settings
- email settings
- push notification settings

==================================================
33. API CONFIGURATION
==================================================

Jangan hard-code credential.

Buat management panel:

DigiFlazz:
- username
- API key
- webhook configuration
- environment

BuatQris:
- account ID
- secret token
- webhook secret
- environment

Tampilkan secret secara masked.

Contoh:

sk_live_••••••••••1234

Hanya Super Admin yang dapat mengubah credential.

==================================================
34. DATABASE
==================================================

Design normalized relational database.

Minimal entities:

users
roles
permissions
role_permissions
user_sessions
email_otps
transaction_pins
wallets
wallet_ledger
wallet_locks
transactions
transaction_items
products
product_categories
product_prices
pricing_rules
providers
provider_products
provider_transactions
deposits
withdrawals
withdrawal_destinations
internal_transfers
notifications
notification_reads
audit_logs
webhook_events
webhook_deliveries
system_settings
admin_actions
security_events

Tambahkan foreign keys.

Indexes untuk query penting.

Unique constraints untuk reference ID.

Jangan menyimpan informasi redundan tanpa alasan.

==================================================
35. FINANCIAL DATA INTEGRITY
==================================================

Gunakan integer untuk nominal rupiah.

Jangan gunakan floating point untuk uang.

Contoh:

Rp10.000
disimpan:

10000

Gunakan database transaction untuk setiap perubahan finansial.

Gunakan locking untuk wallet yang sedang diproses.

==================================================
36. API LAYER
==================================================

Buat internal API layer untuk frontend.

Contoh:

/api/auth/*
/api/user/*
/api/wallet/*
/api/products/*
/api/transactions/*
/api/deposit/*
/api/withdraw/*
/api/transfer/*
/api/notifications/*

Provider API:

/services/digiflazz/*
/services/buatqris/*

Webhook:

/webhooks/digiflazz
/webhooks/buatqris

Jangan expose provider credentials.

==================================================
37. ERROR HANDLING
==================================================

Error harus user-friendly.

Jangan tampilkan:

SQL error
PHP stack trace
API secret
database error
internal paths

kepada User.

Gunakan:

"Transaksi sedang diproses."

"Saldo tidak mencukupi."

"Transaksi gagal. Silakan coba lagi."

Detail teknis hanya masuk log.

==================================================
38. UI/UX QUALITY
==================================================

Gunakan:

- consistent spacing
- typography hierarchy
- premium iconography
- subtle animation
- skeleton loading
- empty states
- error states
- success states
- confirmation states
- disabled states
- hover states
- focus states
- loading states

Jangan menggunakan emoji sebagai icon utama.

Gunakan icon library profesional.

Animation:
100–250ms untuk micro interaction.

Jangan membuat semua elemen bergerak.

==================================================
39. MOBILE UX
==================================================

Mobile navigation:

Home
Products
Transfer
History
Profile

Gunakan bottom navigation.

Action penting dapat menggunakan floating/action button bila benar-benar diperlukan.

Deposit/Transfer/Withdraw dapat menggunakan bottom sheet atau dedicated mobile page.

==================================================
40. DESKTOP UX
==================================================

Desktop:

Sidebar
Topbar
Content
Notification center
Profile menu

Super Admin:
professional management dashboard.

User:
simpler dashboard.

==================================================
41. DESIGN ANTI AI-SLOP
==================================================

Jangan menghasilkan:

- 10 card statistik yang sama
- gradient everywhere
- giant heading
- unnecessary glass panels
- random icons
- excessive rounded corners
- excessive shadows
- fake charts
- lorem ipsum
- meaningless dashboard metrics
- excessive animations

Setiap komponen harus memiliki alasan UX.

Gunakan visual hierarchy.

Design harus terlihat seperti hasil kerja:
Senior Product Designer + Senior Frontend Engineer.

==================================================
42. ACCESSIBILITY
==================================================

Implement:

- keyboard navigation
- focus states
- readable contrast
- semantic HTML
- aria labels
- accessible form controls

==================================================
43. PERFORMANCE
==================================================

Optimalkan:

- lazy loading
- image optimization
- CSS
- JavaScript bundle
- database indexes
- pagination
- caching jika aman
- API timeout
- retry policy

Jangan membuat request provider secara berulang tanpa alasan.

==================================================
44. LOGGING & MONITORING
==================================================

Buat structured application logging.

Log:

- authentication
- provider request
- provider response
- webhook
- financial event
- security event
- exceptions

Jangan pernah log:

- password
- PIN
- API secret
- secret token
- OTP plaintext

==================================================
45. TESTING
==================================================

Buat testing strategy.

Minimal test:

Authentication
Authorization
Wallet
Deposit
Withdrawal
Transfer
PPOB
Pricing
Webhook
Idempotency
Refund
Permission
Rate limiting

Sangat penting:

Test scenario:

User memiliki Rp100.000.

Request transfer Rp80.000 dikirim dua kali secara bersamaan.

Expected:

Saldo sender hanya berkurang Rp80.000 satu kali.

Bukan Rp160.000.

Test:

Webhook deposit dikirim dua kali.

Expected:

Saldo hanya bertambah satu kali.

==================================================
46. LOCAL DEVELOPMENT
==================================================

Project harus mudah dijalankan menggunakan XAMPP.

Dokumentasikan:

1. Install XAMPP
2. Apache
3. MySQL
4. Clone/copy project
5. Create database
6. Import migration/schema
7. Configure environment
8. Configure mail
9. Configure API credentials
10. Run application

==================================================
47. CPANEL DEPLOYMENT
==================================================

Buat deployment yang realistis untuk cPanel.

Dokumentasikan:

- upload files
- create MySQL database
- create database user
- import database
- configure environment
- configure PHP version
- configure SSL
- configure cron jobs jika diperlukan
- configure webhook URL
- configure email
- configure document root

Jangan membutuhkan Docker untuk production.

Jangan membutuhkan Node.js runtime di server production.

==================================================
48. CRON / BACKGROUND TASK
==================================================

Jika membutuhkan background task:

gunakan cPanel Cron Jobs.

Contoh:

- pending transaction reconciliation
- expired deposit cleanup
- provider status synchronization
- notification processing
- system maintenance

Pastikan cron job idempotent.

==================================================
49. FINANCIAL SAFETY
==================================================

Prioritas utama:

1. Money correctness
2. Transaction integrity
3. Security
4. Reliability
5. UX
6. Visual design

Jangan mengorbankan financial integrity demi UI.

==================================================
50. IMPLEMENTATION STRATEGY
==================================================

Jangan langsung membuat semua fitur sekaligus tanpa struktur.

Kerjakan secara bertahap:

PHASE 1
Architecture
Database
Authentication
RBAC
Base UI

PHASE 2
Wallet
Ledger
Deposit
BuatQris

PHASE 3
DigiFlazz
Product catalog
Pricing engine
PPOB transaction

PHASE 4
Transfer
QR transfer
Withdrawal

PHASE 5
Notification
Audit
Reports

PHASE 6
Super Admin
Admin management
System settings

PHASE 7
Security hardening

PHASE 8
Testing

PHASE 9
Performance optimization

PHASE 10
Production deployment

==================================================
51. IMPORTANT ENGINEERING RULE
==================================================

Jangan membuat fitur palsu.

Jika API provider belum memiliki credential:
buat integration layer dan configuration interface.

Gunakan mock/sandbox hanya untuk development.

Jangan membuat seolah-olah transaksi benar-benar berhasil jika provider belum terhubung.

==================================================
52. PROVIDER INTEGRATION RULE
==================================================

Selalu ikuti dokumentasi API provider terbaru.

Jangan menebak parameter API.

Jangan mengarang endpoint.

Jika dokumentasi API berubah, buat integration layer yang mudah diperbarui.

Semua provider-specific logic harus berada di service/provider layer.

Jangan menyebarkan kode DigiFlazz/BuatQris ke seluruh aplikasi.

==================================================
53. FINAL PRODUCT FEEL
==================================================

FanzPay harus terasa seperti:

"Fintech modern yang sederhana digunakan tetapi memiliki backend yang sangat serius."

Mobile:
Android fintech app vibe.

Desktop:
Premium fintech dashboard.

Visual:
Blue identity dari logo FanzPay.

UX:
Fast, clean, intuitive.

Engineering:
Secure, modular, maintainable.

==================================================
54. FINAL REQUIREMENT
==================================================

Sebelum menyelesaikan implementasi:

- periksa seluruh route
- periksa seluruh permission
- periksa semua financial flow
- periksa database relation
- periksa webhook
- periksa idempotency
- periksa race condition
- periksa security
- periksa responsive layout
- periksa mobile UX
- periksa desktop UX
- periksa cPanel compatibility
- periksa XAMPP compatibility

Jangan berhenti hanya karena UI sudah terlihat bagus.

Aplikasi dianggap selesai hanya jika:

UI + backend + database + security + wallet + provider integration + transaction handling + admin management + deployment architecture

semuanya memiliki fondasi yang benar.

Jika terdapat keputusan teknis yang belum ditentukan, pilih solusi yang paling:
- secure
- maintainable
- scalable
- cPanel-friendly
- cost-efficient
- production-ready

dan dokumentasikan alasan keputusan tersebut.