-- =====================================================================
-- FanzPay migrasi Midtrans Core API (idempoten, aman dijalankan ulang).
-- Menambah kolom gateway multi-metode pada deposits + seed pengaturan.
-- Jalankan: php web/database/migrate_midtrans.php
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE deposits
  ADD COLUMN IF NOT EXISTS gateway VARCHAR(20) NOT NULL DEFAULT 'midtrans' AFTER provider_txn_id,
  ADD COLUMN IF NOT EXISTS payment_type VARCHAR(30) NOT NULL DEFAULT 'qris' AFTER gateway,
  ADD COLUMN IF NOT EXISTS payment_code VARCHAR(80) NULL AFTER payment_type,
  ADD COLUMN IF NOT EXISTS qr_string TEXT NULL AFTER payment_code,
  ADD COLUMN IF NOT EXISTS actions JSON NULL AFTER qr_string,
  ADD COLUMN IF NOT EXISTS raw_response JSON NULL AFTER actions,
  ADD COLUMN IF NOT EXISTS credited_at DATETIME NULL AFTER paid_at;

-- cPanel/MariaDB lama tidak mendukung IF NOT EXISTS pada ADD COLUMN;
-- runner PHP di migrate_midtrans.php menangani fallback tersebut.

CREATE INDEX IF NOT EXISTS idx_dep_gateway ON deposits (gateway, status);
CREATE INDEX IF NOT EXISTS idx_dep_expired ON deposits (status, expired_at);
