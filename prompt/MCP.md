# MASTER PROMPT — IMPLEMENTASI MCP SERVER FanzPay AI-Powered PPOB

## 1. PERAN DAN MISI

Bertindak sebagai Senior Software Architect, Senior PHP Native Engineer, MCP Protocol Engineer, Fintech Security Engineer, Database Engineer, QA Engineer, dan DevSecOps Engineer.

Tugas utama adalah mengaudit, merancang, mengimplementasikan, menguji, dan mendokumentasikan fitur **FanzPay MCP Server**, sehingga pelanggan dapat menghubungkan akun FanzPay dengan AI provider eksternal yang mendukung MCP, misalnya ChatGPT atau klien AI lain yang kompatibel.

Tujuan produk adalah memberikan pengalaman interaksi AI yang dapat:
- Membaca saldo dan informasi akun pelanggan yang terautentikasi.
- Mencari, membandingkan, dan merekomendasikan produk PPOB.
- Membeli produk PPOB melalui backend FanzPay sesuai izin pelanggan.
- Menganalisis riwayat dan pola pengeluaran transaksi pelanggan.
- Membantu pelanggan memeriksa status transaksi dan deposit.
- Memberikan bantuan customer service berbasis data FanzPay yang memang boleh diakses.
- Menyediakan rekomendasi, informasi promo, dan pengingat terkait PPOB apabila didukung oleh data dan fitur aplikasi yang sudah tersedia.

Jangan membangun chatbot internal sebagai pengganti integrasi MCP. Antarmuka utama adalah AI eksternal yang terhubung ke MCP Server FanzPay.

MCP hanya menjadi lapisan integrasi. Seluruh keputusan bisnis, autentikasi, otorisasi, dan eksekusi transaksi tetap berada di backend FanzPay.

## 2. KONTEKS DAN BATASAN TEKNOLOGI

Kondisi dasar proyek:
- Nama aplikasi: FanzPay.
- Jenis aplikasi: PPOB.
- Backend: PHP Native.
- Database: MySQL.
- Target hosting: cPanel.
- Provider produk PPOB: DigiFlazz.
- Payment gateway deposit: Midtrans.
- Notifikasi: gunakan sistem notifikasi yang sudah tersedia di FanzPay.
- Pengaturan dan pengawasan: Panel Super Admin.
- Integrasi AI: MCP melalui koneksi HTTPS.
- AI provider: semua klien yang kompatibel dengan MCP dan mendukung mekanisme koneksi serta autentikasi yang dibutuhkan.
- Model AI dibayar dan diakses oleh pelanggan melalui akun AI mereka sendiri. FanzPay tidak boleh menganggap dirinya menyediakan atau membayar akses model AI pelanggan.

Pertahankan struktur, desain, konvensi, autentikasi, database, dan integrasi yang sudah ada selama masih layak dan aman.

Jangan melakukan migrasi framework, mengganti backend dengan Node.js, menambahkan layanan AI berbayar milik FanzPay, atau memindahkan hosting tanpa kebutuhan teknis yang terbukti.

Jika kemampuan hosting cPanel saat ini tidak mendukung server MCP yang dibutuhkan, lakukan analisis keterbatasan terlebih dahulu. Usulkan service terpisah hanya jika diperlukan, dengan PHP Native tetap menjadi backend bisnis utama.

Jangan mengasumsikan semua klien MCP mendukung metode autentikasi, transport, atau fitur yang sama. Gunakan spesifikasi MCP dan dokumentasi resmi versi yang relevan saat implementasi.

## 3. TAHAP PERTAMA: AUDIT WAJIB SEBELUM MENGUBAH KODE

Sebelum implementasi:

1. Periksa seluruh struktur repository dan entry point aplikasi.
2. Identifikasi versi PHP, MySQL, ekstensi PHP, konfigurasi web server, dan keterbatasan cPanel.
3. Identifikasi sistem autentikasi, sesi, PIN transaksi, role, dan permission yang sudah tersedia.
4. Audit skema tabel saldo, mutasi, transaksi, produk, pengguna, deposit, notifikasi, dan audit log.
5. Audit integrasi DigiFlazz dan Midtrans yang sudah berjalan.
6. Identifikasi cara FanzPay menghitung harga jual, margin, saldo tersedia, dan status transaksi.
7. Identifikasi mekanisme callback/webhook provider dan proteksi autentikasinya.
8. Periksa apakah saldo dan transaksi telah menggunakan database transaction, locking, dan idempotency.
9. Identifikasi seluruh file konfigurasi rahasia dan pastikan rahasia tidak berada dalam source code yang dipublikasikan.
10. Catat potensi bug, konflik, dan risiko kompatibilitas.

Jangan menebak nama tabel, nama fungsi, endpoint, atau struktur repository. Periksa implementasi aktual terlebih dahulu.

Buat dokumen:
- docs/mcp-audit.md
- docs/mcp-architecture.md
- docs/mcp-threat-model.md
- docs/mcp-implementation-plan.md

Setiap rekomendasi harus dikaitkan dengan kondisi nyata repository.

Setelah audit, lanjutkan implementasi tanpa berhenti hanya pada dokumen rencana, kecuali terdapat blocker nyata yang memerlukan keputusan pemilik proyek.

## 4. ARSITEKTUR TARGET

Bangun arsitektur dengan lapisan berikut:

A. MCP transport dan protocol layer
- Menerima koneksi dari klien MCP yang kompatibel.
- Menggunakan transport dan lifecycle protocol sesuai spesifikasi MCP yang berlaku.
- Menangani initialization, negosiasi kemampuan, penemuan tools, pemanggilan tools, dan penanganan error.
- Mendukung koneksi HTTPS dengan konfigurasi server yang benar.
- Tidak menganggap endpoint HTTP biasa sebagai MCP Server tanpa implementasi protokol yang valid.

B. Authentication dan authorization layer
- Mengidentifikasi pelanggan melalui identitas yang terverifikasi.
- Menghubungkan sesi atau token MCP dengan satu akun FanzPay yang jelas.
- Menegakkan permission pada setiap pemanggilan tool.
- Mengelola persetujuan akses, pencabutan akses, kedaluwarsa token, dan sesi yang dicurigai.
- Mengikuti standar autentikasi MCP yang sesuai dengan spesifikasi dan kemampuan klien.

C. Business service layer
- Menggunakan service dan aturan bisnis FanzPay yang sudah ada.
- Tidak menduplikasi logika saldo atau transaksi di MCP Server.
- Menggunakan fungsi bisnis yang sama dengan aplikasi web FanzPay.
- Memisahkan pembacaan data, persiapan transaksi, otorisasi, dan eksekusi transaksi.

D. Database layer
- Menggunakan MySQL dengan prepared statements.
- Menggunakan database transaction, row locking atau mekanisme concurrency control yang sesuai, unique constraints, dan idempotency.
- Menjaga integritas saldo dan ledger.
- Tidak memberikan akses SQL bebas kepada model AI.

E. Provider integration layer
- DigiFlazz: pencarian produk, validasi harga, pengiriman transaksi, dan rekonsiliasi status.
- Midtrans: pembacaan status deposit yang dimiliki pelanggan melalui integrasi yang sudah ada.
- Notifikasi: pemberitahuan peristiwa dan status transaksi melalui mekanisme yang tersedia.

Jangan membuat ulang sistem pembayaran yang sudah ada apabila service yang ada dapat digunakan dengan aman.

## 5. DAFTAR MCP TOOLS

Rancang tools dengan nama yang konsisten, deskripsi yang jelas, input schema yang ketat, output yang terstruktur, dan permission per tool.

Nama di bawah merupakan target desain. Cocokkan dengan skema serta fungsi nyata proyek sebelum implementasi.

### A. Informasi akun

1. get_my_profile
   - Membaca informasi profil dasar akun terautentikasi.
   - Hanya mengembalikan data yang diperlukan.
   - Jangan mengungkap kredensial, hash PIN, OTP, token, atau informasi sensitif.

2. get_my_balance
   - Membaca saldo yang tersedia.
   - Jika relevan, bedakan saldo tersedia dan saldo yang sedang ditahan.
   - Jangan mengubah saldo.

3. get_my_transaction_history
   - Membaca riwayat transaksi milik pelanggan.
   - Mendukung pagination dan batas jumlah hasil.
   - Mendukung filter tanggal, kategori, dan status yang tervalidasi.
   - Jangan mengembalikan data pelanggan lain.

4. get_my_transaction_status
   - Memeriksa status transaksi berdasarkan identifier yang dimiliki pelanggan.
   - Memastikan kepemilikan transaksi pada setiap permintaan.

5. get_my_deposit_status
   - Membaca status deposit milik pelanggan.
   - Tidak dapat membuat pembayaran, mengubah saldo, atau menganggap redirect pembayaran sebagai bukti pembayaran.

### B. Katalog dan rekomendasi PPOB

6. search_ppob_products
   - Mencari produk berdasarkan kategori, operator, nominal, dan kriteria lain yang didukung sistem.
   - Mendukung pulsa, paket data, token PLN, voucher game, dan kategori lain hanya jika benar-benar tersedia pada katalog FanzPay.
   - Mengambil informasi produk dan harga terbaru dari sumber data yang benar.

7. compare_ppob_products
   - Membandingkan harga jual, nominal, deskripsi, masa berlaku, dan atribut relevan yang tersedia.
   - Tidak mengarang atribut produk yang tidak disediakan provider.

8. recommend_ppob_products
   - Memberikan rekomendasi berdasarkan kebutuhan yang disebut pelanggan, anggaran, kategori produk, dan saldo yang tersedia jika diizinkan.
   - Jelaskan alasan rekomendasi secara ringkas.
   - Jangan mengklaim bahwa produk pasti paling murah atau terbaik tanpa data pembanding yang memadai.
   - Rekomendasi tidak boleh otomatis menjadi otorisasi pembelian.

9. get_ppob_product_details
   - Mengambil detail produk berdasarkan identifier katalog yang valid.
   - Memastikan produk masih aktif dan dapat dijual sebelum pembelian.

### C. Pembelian PPOB

10. prepare_ppob_purchase
    - Memvalidasi input pelanggan dan menyusun ringkasan transaksi.
    - Mengembalikan identifier persiapan transaksi yang acak, memiliki masa berlaku, terikat pada pelanggan, dan tidak dapat digunakan untuk transaksi lain.
    - Menampilkan produk, nomor tujuan, harga jual aktual, biaya jika ada, total debit, dan masa berlaku penawaran.
    - Tidak melakukan debit atau mengirim transaksi ke provider.
    - Tidak menerima harga jual final dari AI sebagai sumber kebenaran.

11. execute_ppob_purchase
    - Menjalankan pembelian yang sudah dipersiapkan dan lolos pemeriksaan izin.
    - Memverifikasi kepemilikan persiapan transaksi.
    - Menghitung ulang harga dan saldo di sisi server.
    - Memeriksa izin pembelian otomatis, batas nominal, batas harian, saldo, status produk, dan aturan transaksi.
    - Menerapkan idempotency untuk mencegah transaksi ganda.
    - Mengembalikan status dan identifier transaksi yang sebenarnya.
    - Tidak menerima PIN atau OTP melalui input tool MCP.
    - Jika autentikasi tambahan diwajibkan, hentikan proses dan arahkan pelanggan ke alur autentikasi resmi FanzPay.

12. get_purchase_policy
    - Membaca status dan batas pembelian otomatis yang berlaku untuk pelanggan tersebut.
    - Tidak dapat mengubah kebijakan.

Jangan mengekspos tool yang dapat melakukan perubahan kebijakan hanya karena AI memintanya.

### D. Analitik dan customer service

13. get_my_spending_summary
    - Menganalisis pengeluaran pelanggan berdasarkan riwayat transaksi yang tersedia.
    - Mendukung ringkasan mingguan dan bulanan jika rentang data memadai.
    - Memisahkan transaksi berhasil, pending, gagal, dan refund.
    - Jangan menghitung transaksi pending sebagai pembelian final tanpa penjelasan.

14. explain_my_transaction
    - Menjelaskan informasi transaksi milik pelanggan berdasarkan catatan sistem.
    - Tidak boleh membuat alasan kegagalan yang tidak diketahui.

15. search_fanzpay_help
    - Mencari jawaban dari basis pengetahuan resmi FanzPay.
    - Jawaban harus relevan dengan pertanyaan pelanggan.

16. create_customer_support_ticket
    - Membuat tiket pengaduan atas permintaan pelanggan.
    - Memvalidasi kepemilikan transaksi yang dilaporkan.
    - Mengembalikan nomor tiket.
    - Tidak boleh menjanjikan refund atau perubahan saldo.

17. get_fanzpay_promotions
    - Mengambil promo yang aktif dan benar-benar tersedia.
    - Memeriksa periode, syarat, kategori produk, dan kelayakan pelanggan jika didukung.

18. get_ppob_reminders
    - Mengambil pengingat PPOB yang telah disediakan atau diizinkan pelanggan.
    - Jangan mengirim notifikasi tanpa persetujuan dan mekanisme yang sah.

Tools tambahan hanya boleh dibuat jika memiliki kebutuhan bisnis yang jelas, batas akses yang jelas, dan pengujian keamanan yang sesuai.

Jangan membuat tools untuk transfer antarpelanggan, penarikan saldo, pengelolaan akun Super Admin, pengubahan saldo, atau akses administratif karena fitur tersebut berada di luar lingkup implementasi ini.

## 6. KEBIJAKAN PEMBELIAN OTOMATIS

Pelanggan menginginkan pembelian otomatis di bawah batas nominal tertentu.

Implementasikan fitur ini sebagai opt-in yang secara default nonaktif.

Sebelum fitur aktif:
- Pelanggan harus masuk ke FanzPay melalui mekanisme autentikasi resmi.
- Pelanggan harus secara eksplisit mengaktifkan pembelian otomatis.
- Pelanggan harus menetapkan batas nominal per transaksi.
- Pelanggan harus menetapkan batas akumulasi harian.
- Pelanggan harus menetapkan kategori produk yang diizinkan.
- Pelanggan harus menentukan aturan untuk nomor tujuan, termasuk apakah nomor baru diperbolehkan.
- Pelanggan harus melihat penjelasan risiko dan menyetujui kebijakan.
- Perubahan kebijakan sensitif harus menggunakan PIN melalui alur resmi FanzPay.

Simpan konfigurasi di database dan ikat ke user_id yang terautentikasi.

Batas yang ditetapkan Super Admin adalah batas maksimum sistem. Pelanggan hanya boleh menetapkan batas yang sama atau lebih rendah.

Setiap pembelian otomatis harus mematuhi seluruh syarat berikut:
1. Fitur pembelian otomatis aktif.
2. Kategori dan jenis produk diizinkan.
3. Nilai transaksi berada di bawah batas per transaksi.
4. Akumulasi pembelian tidak melebihi batas harian.
5. Saldo mencukupi.
6. Produk aktif dan harga masih valid.
7. Nomor tujuan valid.
8. Akun dan sesi memenuhi syarat keamanan.
9. Tidak terdapat duplikasi transaksi.
10. Tidak ada pemblokiran risiko atau pembatasan akun.

Permintaan bahasa alami tidak boleh mengubah batas permanen yang tersimpan.

Contoh: permintaan "beli produk apa saja sampai Rp500.000" tidak boleh mengesampingkan batas yang telah ditetapkan pelanggan.

Jika permintaan tidak memenuhi kebijakan:
- Jangan mengeksekusi transaksi.
- Jelaskan alasan penolakan.
- Arahkan pelanggan ke pengaturan atau autentikasi resmi FanzPay jika diperlukan.

PIN tidak boleh disimpan dalam konfigurasi pembelian otomatis, log, prompt, percakapan, atau database dalam bentuk plaintext.

Jangan meminta pelanggan mengirim PIN melalui ChatGPT atau provider AI lainnya.

## 7. KEBIJAKAN KEAMANAN

Terapkan prinsip least privilege, deny by default, defense in depth, dan zero trust.

### A. Autentikasi MCP
- Terapkan autentikasi yang sesuai spesifikasi MCP dan standar yang relevan.
- Gunakan OAuth 2.1 dengan praktik keamanan yang sesuai apabila diwajibkan atau didukung oleh arsitektur klien.
- Jangan membuat OAuth palsu yang hanya menyerupai standar.
- Jangan menggunakan satu API key global untuk seluruh pelanggan.
- Ikat token ke identitas pelanggan, scope, audience, masa berlaku, dan sesi sesuai mekanisme yang dipilih.
- Terapkan revocation dan rotasi kredensial.
- Hindari penyimpanan token jangka panjang di browser atau tempat yang dapat dibaca pihak lain.
- Jangan mengandalkan user_id yang dikirim oleh AI untuk menentukan identitas pelanggan.

### B. Otorisasi per pelanggan
- Identitas pelanggan harus berasal dari konteks autentikasi server.
- Setiap query dan tool harus memverifikasi kepemilikan resource.
- Jangan percaya kepada instruksi model AI mengenai identitas, role, atau permission.
- Jangan pernah mengizinkan pelanggan membaca transaksi, saldo, profil, atau tiket milik pelanggan lain.
- Terapkan scope read-only dan pembelian secara terpisah bila memungkinkan.
- Sediakan cara mencabut koneksi MCP dari akun FanzPay.

### C. PIN transaksi
- Gunakan implementasi PIN yang sudah ada jika aman.
- Verifikasi PIN hanya di backend FanzPay melalui alur autentikasi resmi.
- Jangan pernah menerima PIN melalui percakapan AI atau argumen MCP.
- Terapkan rate limiting, penundaan atau penguncian setelah kegagalan berulang, dan audit log.
- Jangan menyimpan PIN plaintext atau menuliskannya ke log.
- Jika PIN belum ada atau implementasinya tidak aman, laporkan kebutuhan perbaikannya dan jangan melewati autentikasi untuk membuat demo terlihat berfungsi.

### D. Pencegahan prompt injection
- Perlakukan semua input AI, hasil tools, deskripsi produk, catatan pelanggan, dan konten provider sebagai data tidak tepercaya.
- Instruksi yang berasal dari hasil pencarian, deskripsi produk, atau pesan pelanggan tidak boleh mengubah kebijakan backend.
- Model AI tidak boleh menentukan permission sendiri.
- Jangan mengeksekusi SQL, PHP, shell command, atau URL arbitrer berdasarkan instruksi model.
- Validasi seluruh input di server meskipun sudah divalidasi oleh JSON Schema.
- Batasi ukuran request, panjang string, jumlah hasil, dan frekuensi pemanggilan tools.
- Jangan menyertakan rahasia atau data pelanggan yang tidak diperlukan dalam konteks model.

### E. Keamanan HTTP dan MCP
- Wajib menggunakan HTTPS pada production.
- Validasi Origin dan host untuk mencegah DNS rebinding serta serangan berbasis browser bila relevan dengan transport yang dipilih.
- Terapkan proteksi SSRF, pembatasan ukuran body, rate limiting, dan batas waktu request.
- Jangan mengizinkan target URL arbitrer atau koneksi keluar yang tidak diperlukan.
- Gunakan error handling yang tidak membocorkan stack trace, SQL, token, atau detail infrastruktur.
- Jangan menonaktifkan validasi TLS.

### F. Keamanan database
- Gunakan PDO atau mekanisme prepared statements yang setara.
- Gunakan validasi tipe dan constraint database.
- Hindari SQL concatenation untuk input pengguna.
- Terapkan transaksi database dan locking yang sesuai saat melakukan debit atau reservasi saldo.
- Catat setiap mutasi saldo melalui ledger yang konsisten dengan sistem FanzPay.
- Jangan memberikan akses database langsung kepada MCP Server tanpa lapisan service dan aturan bisnis.

### G. Rahasia dan konfigurasi
- Simpan API key DigiFlazz, Midtrans, secret OAuth, dan rahasia lain di environment atau konfigurasi server yang terlindungi.
- Jangan mengirim rahasia ke model AI.
- Jangan commit .env, token, private key, atau kredensial production ke Git.
- Jangan menampilkan secret di dashboard, log, response MCP, atau pesan error.
- Gunakan sandbox untuk pengujian integrasi pembayaran jika tersedia.

## 8. INTEGRITAS TRANSAKSI DAN SALDO

Ini adalah bagian kritis.

Jangan menganggap pengiriman request ke DigiFlazz berarti transaksi berhasil.

Implementasikan state machine transaksi sesuai kemampuan provider dan struktur FanzPay, misalnya:
- prepared
- processing
- pending
- successful
- failed
- reversed atau refunded jika memang didukung

Jangan mengubah status terminal secara sembarangan.

Ketika membeli produk:
1. Validasi pelanggan dan izin.
2. Validasi persiapan transaksi.
3. Ambil harga aktual dari sumber tepercaya.
4. Validasi saldo dan batas nominal.
5. Cegah request duplikat dengan idempotency key.
6. Gunakan locking atau mekanisme concurrency control untuk mencegah dua transaksi menghabiskan saldo yang sama.
7. Catat transaksi dan mutasi sesuai pola ledger aplikasi.
8. Jalankan integrasi provider dengan penanganan timeout dan kegagalan.
9. Rekonsiliasi status provider.
10. Lepaskan atau kembalikan saldo yang ditahan hanya sesuai hasil transaksi yang terverifikasi.
11. Kirim notifikasi berdasarkan status yang benar.
12. Catat audit trail.

Rancang secara khusus untuk kasus:
- Request terkirim tetapi respons provider timeout.
- Pelanggan mengirim perintah yang sama berulang kali.
- Dua request pembelian bersamaan.
- Produk berubah harga setelah persiapan transaksi.
- Saldo berubah ketika transaksi sedang diproses.
- Callback provider terlambat atau dikirim berulang.
- Provider menyatakan transaksi pending.
- Aplikasi mengalami crash di tengah proses.
- Pelanggan mencabut akses MCP ketika ada transaksi yang sedang berjalan.

Jangan melakukan retry pembelian yang berpotensi menggandakan transaksi tanpa memeriksa status transaksi sebelumnya.

Jika status provider belum diketahui, tandai pending atau status rekonsiliasi yang sesuai. Jangan menganggap gagal dan mengembalikan saldo secara otomatis jika transaksi sebenarnya mungkin sudah diproses provider.

Jangan membuat perubahan pada saldo melalui endpoint MCP khusus. MCP hanya boleh memanggil business service transaksi yang sudah tervalidasi.

## 9. MIDTRANS DAN DIGIFLAZZ

Gunakan integrasi aktual yang ada di repository.

DigiFlazz:
- Gunakan endpoint dan autentikasi yang sesuai dokumentasi resmi.
- Validasi harga jual menggunakan perhitungan backend FanzPay.
- Jangan mengungkap harga modal atau margin internal jika kebijakan FanzPay hanya menampilkan harga jual.
- Validasi SKU, nomor tujuan, dan status produk.
- Gunakan mekanisme callback, pengecekan status, dan rekonsiliasi yang benar.
- Verifikasi autentisitas callback sebelum memperbarui status.

Midtrans:
- MCP hanya membaca status deposit milik pelanggan.
- Status deposit harus berasal dari data backend yang terverifikasi.
- Jangan menganggap halaman redirect atau klaim pelanggan sebagai bukti pembayaran.
- Verifikasi signature dan keaslian notifikasi sesuai dokumentasi resmi Midtrans.
- Jangan membuat deposit atau mengubah saldo hanya berdasarkan instruksi AI.
- Jangan mengubah alur deposit yang telah berjalan tanpa audit dan pengujian regresi.

## 10. PANEL SUPER ADMIN

Tambahkan pengaturan MCP ke panel Super Admin yang sudah ada, dengan akses khusus untuk role Super Admin sesuai sistem otorisasi aktual.

Fitur yang dibutuhkan:
- Mengaktifkan dan menonaktifkan MCP Server.
- Mengaktifkan dan menonaktifkan masing-masing tool.
- Mengatur batas nominal maksimum pembelian otomatis.
- Mengatur batas akumulasi transaksi harian sistem.
- Mengatur kategori produk yang diizinkan untuk otomatisasi.
- Mengatur rate limit dan batas pemanggilan tool.
- Melihat koneksi MCP aktif jika informasi tersebut tersedia.
- Mencabut koneksi pelanggan yang terindikasi bermasalah.
- Melihat ringkasan penggunaan dan audit log.
- Melihat error, kegagalan autentikasi, dan transaksi pending.
- Mengatur maintenance mode.
- Mengaktifkan atau menonaktifkan fitur berdasarkan status risiko sistem.

Semua perubahan kebijakan harus diverifikasi di backend, bukan hanya di frontend.

Terapkan audit log yang merekam pelaku, tindakan, waktu, perubahan kebijakan, dan hasil operasi tanpa merekam PIN, token, atau rahasia.

Jangan menambahkan tools MCP administratif kepada pelanggan. Panel Super Admin tetap berada di luar lingkup tools pelanggan.

## 11. NOTIFIKASI DAN TRANSPARANSI

Gunakan mekanisme notifikasi yang sudah tersedia.

Berikan notifikasi untuk:
- Koneksi AI baru berhasil diotorisasi.
- Akses MCP dicabut.
- Pembelian berhasil, gagal, atau pending.
- Penolakan transaksi karena batas nominal atau kebijakan.
- Perubahan pengaturan pembelian otomatis.
- Aktivitas mencurigakan yang memenuhi kriteria keamanan.

Notifikasi harus membedakan:
- Perintah diterima.
- Transaksi diproses.
- Transaksi berhasil.
- Transaksi gagal.
- Status belum diketahui atau masih pending.

Jangan mengirim informasi sensitif secara berlebihan melalui notifikasi.

## 12. PRIVASI DAN AUDIT LOG

Terapkan minimisasi data.

Jangan menyimpan seluruh percakapan AI apabila tidak diperlukan.

Audit log harus mencatat:
- Identitas pelanggan internal yang terautentikasi.
- Nama tool.
- Waktu pemanggilan.
- Hasil otorisasi.
- Identifier request dan transaksi.
- Status eksekusi.
- Kode error yang aman.
- Informasi relevan untuk investigasi.

Jangan mencatat:
- PIN.
- OTP.
- Access token atau refresh token.
- Secret provider.
- Password.
- Data pribadi yang tidak dibutuhkan.
- Seluruh prompt pelanggan secara default apabila berisi informasi sensitif.

Tentukan retensi log yang proporsional dan sediakan pembatasan akses terhadap log.

## 13. PENGUJIAN WAJIB

Buat automated tests sesuai kemampuan proyek dan sediakan dokumentasi cara menjalankannya.

A. Unit tests:
- Validasi input setiap tool.
- Validasi batas transaksi.
- Perhitungan harga jual.
- Validasi status produk.
- Permission pelanggan.
- Idempotency.
- Penanganan status transaksi.
- Redaksi informasi sensitif.

B. Integration tests:
- MCP initialization.
- Discovery tools.
- Pemanggilan tool read-only.
- Pemanggilan pembelian.
- Autentikasi dan pencabutan token.
- Integrasi mock DigiFlazz.
- Integrasi mock Midtrans.
- Pengiriman notifikasi.
- Integrasi MySQL dan transaction locking.

C. Security tests:
- Akses lintas akun.
- Pemalsuan user_id.
- Token tidak valid, kedaluwarsa, atau dicabut.
- Scope yang tidak sesuai.
- Brute-force PIN.
- SQL injection.
- XSS pada data yang ditampilkan.
- SSRF.
- Request replay.
- Duplicate purchase.
- Race condition.
- Prompt injection.
- Input yang sangat besar atau malformed.
- Penyalahgunaan rate limit.
- Akses tool yang telah dinonaktifkan Super Admin.
- Kebocoran rahasia melalui log atau error.
- Manipulasi harga dan identifier transaksi.

D. Failure tests:
- Timeout provider.
- Callback duplikat.
- Callback tidak autentik.
- Database error.
- Koneksi terputus.
- Crash di tengah transaksi.
- Transaksi pending yang berkepanjangan.
- Kegagalan notifikasi.
- Perubahan saldo bersamaan.

E. Regression tests:
- Login dan autentikasi lama tetap bekerja.
- Alur deposit Midtrans tetap bekerja.
- Pembelian PPOB lama tetap bekerja.
- Panel Super Admin tetap berfungsi.
- Perhitungan saldo dan harga tidak berubah secara tidak sengaja.

Jangan menguji transaksi finansial nyata di production tanpa persetujuan eksplisit dan prosedur pengujian yang aman.

## 14. KRITERIA PENERIMAAN

Fitur baru dinyatakan siap hanya jika:
1. Klien MCP yang kompatibel dapat terhubung melalui mekanisme yang terdokumentasi.
2. Tools yang tersedia dapat ditemukan dan dipanggil sesuai protokol.
3. Autentikasi mengikat koneksi ke pelanggan yang benar.
4. Pelanggan tidak dapat mengakses data pelanggan lain.
5. Tools read-only tidak menyebabkan perubahan saldo atau transaksi.
6. Pembelian otomatis default-nya nonaktif.
7. Pembelian otomatis tidak dapat melewati batas sistem atau pelanggan.
8. Transaksi duplikat dicegah.
9. Saldo tetap konsisten pada request bersamaan.
10. Status pending, berhasil, dan gagal ditangani dengan benar.
11. PIN dan secret tidak pernah dikirim ke model AI.
12. Pengaturan Super Admin benar-benar ditegakkan backend.
13. Audit log tidak mengandung kredensial sensitif.
14. Pengujian kritis lulus.
15. Dokumentasi instalasi dan konfigurasi tersedia.
16. Fitur lama FanzPay tidak mengalami regresi yang diketahui.

Jangan menyatakan sistem 100% bebas bug atau aman secara absolut. Nyatakan status berdasarkan hasil pengujian nyata, keterbatasan yang belum terselesaikan, dan risiko tersisa.

## 15. DEPLOYMENT DAN DOKUMENTASI

Buat dokumentasi yang memungkinkan developer memasang dan memelihara MCP Server pada lingkungan cPanel yang kompatibel.

Dokumentasi wajib mencakup:
- Persyaratan versi PHP dan ekstensi.
- Konfigurasi database.
- Konfigurasi environment.
- HTTPS.
- Registrasi klien dan alur otorisasi MCP.
- Konfigurasi DigiFlazz dan Midtrans yang sudah ada.
- Migrasi database yang aman.
- Pengaturan Super Admin.
- Pengaktifan pembelian otomatis.
- Pengujian sebelum production.
- Monitoring dan penanganan error.
- Revokasi koneksi MCP.
- Rollback deployment.
- Prosedur pemulihan transaksi pending.

Jangan menampilkan nilai rahasia nyata dalam dokumentasi.

Jika membutuhkan cron job untuk rekonsiliasi transaksi, dokumentasikan jadwal dan prosedur konfigurasi cPanel.

## 16. FORMAT LAPORAN AKHIR

Setelah implementasi, laporkan:

1. Hasil audit awal.
2. Arsitektur yang benar-benar diterapkan.
3. File yang dibuat dan diubah.
4. Migrasi database yang ditambahkan.
5. MCP tools yang tersedia.
6. Alur autentikasi dan otorisasi.
7. Cara kerja pembelian otomatis.
8. Kontrol keamanan yang diterapkan.
9. Hasil unit, integration, security, dan regression tests.
10. Pengujian yang gagal atau belum dapat dilakukan.
11. Kendala hosting atau kompatibilitas provider.
12. Langkah deployment production.
13. Konfigurasi yang masih harus dilakukan pemilik proyek.
14. Risiko yang masih tersisa.
15. Bukti pengujian dan kriteria penerimaan yang belum terpenuhi.

Bedakan secara tegas:
- Selesai diimplementasikan.
- Sudah diuji dan lulus.
- Baru diuji di lingkungan mock atau sandbox.
- Memerlukan kredensial atau pengujian eksternal.
- Belum selesai.

Jangan mengarang hasil pengujian, jangan mengklaim integrasi live berhasil jika belum diuji, dan jangan berhenti setelah membuat mockup atau dokumentasi.

## INSTRUKSI TERAKHIR

Mulai dengan mengaudit repository aktual. Setelah audit, implementasikan fitur secara bertahap, prioritaskan keamanan dan integritas saldo, lalu jalankan pengujian.

Jangan mengubah scope menjadi chatbot biasa, jangan menambahkan fitur transfer atau penarikan, jangan memberi AI akses SQL bebas, dan jangan mengizinkan model AI melewati otorisasi backend.

Tujuan akhirnya adalah MCP Server FanzPay yang dapat dihubungkan oleh klien AI yang kompatibel, memiliki tools yang benar-benar berfungsi, menjaga data dan saldo pelanggan, serta memiliki hasil pengujian yang dapat diverifikasi.