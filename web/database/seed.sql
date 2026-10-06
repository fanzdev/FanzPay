-- FanzPay seed: permission, provider, kategori, pengaturan dibaca installer.
-- TIDAK ada akun/produk demo. Super admin dibuat via:
--   php web/create-admin.php "Nama" email 08xxxx "PasswordMin8"
-- Katalog produk diambil REAL dari DigiFlazz via: php web/sync.php

INSERT INTO permissions (code, label) VALUES
('dashboard.view','Lihat dashboard'),
('users.view','Lihat pengguna'),
('users.manage','Kelola pengguna'),
('transactions.view','Lihat transaksi'),
('transactions.manage','Kelola transaksi'),
('deposits.view','Lihat deposit'),
('deposits.manage','Kelola deposit'),
('withdrawals.view','Lihat withdrawal'),
('withdrawals.manage','Kelola withdrawal'),
('products.view','Lihat produk'),
('products.manage','Kelola produk & harga'),
('reports.view','Lihat laporan'),
('notifications.manage','Kelola notifikasi'),
('admins.manage','Kelola admin'),
('settings.manage','Kelola pengaturan'),
('apiconfig.manage','Kelola kredensial API'),
('audit.view','Lihat audit log'),
('reconcile.manage','Rekonsiliasi transaksi')
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT INTO providers (code, name, status) VALUES
('digiflazz','DigiFlazz','unknown'),
('buatqris','BuatQris','unknown')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO product_categories (code, name, icon, sort) VALUES
('pulsa','Pulsa','smartphone',10),
('data','Paket Data','wifi',20),
('pln','Listrik PLN','zap',30),
('emoney','E-Money','wallet',40),
('game','Voucher Game','gamepad',50),
('tv','TV & Internet','tv',60),
('pdam','PDAM','droplet',70),
('bpjs','BPJS','shield',80)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- user: id 1 super admin, id 2 user demo (password_hash diisi installer via seed.php)
