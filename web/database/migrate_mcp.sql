-- =====================================================================
-- FanzPay MCP Server migration. Aman di-rerun (IF NOT EXISTS + INSERT IGNORE).
-- Import: mysql -u root fanzpay < migrate_mcp.sql
-- Aplikasi juga memastikan tabel via CREATE TABLE IF NOT EXISTS saat dipakai.
-- =====================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS mcp_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL DEFAULT 'AI connector',
  client VARCHAR(80) NOT NULL DEFAULT 'generic',
  token_hash CHAR(64) NOT NULL,
  scopes VARCHAR(60) NOT NULL DEFAULT 'read',
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  last_used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mcp_token (token_hash),
  KEY idx_mcp_user (user_id),
  CONSTRAINT fk_mcp_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mcp_purchase_policy (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  max_per_tx INT NOT NULL DEFAULT 0,
  max_daily INT NOT NULL DEFAULT 0,
  allowed_categories TEXT NOT NULL,
  allow_new_numbers TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mcp_policy_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mcp_preparations (
  id VARCHAR(48) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_code VARCHAR(60) NOT NULL,
  customer_no VARCHAR(40) NOT NULL,
  sell_price INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'prepared',
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mcp_prep_user (user_id),
  CONSTRAINT fk_mcp_prep_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mcp_support_tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  reference_id VARCHAR(64) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mcp_ticket_user (user_id),
  CONSTRAINT fk_mcp_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mcp_reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  body VARCHAR(255) NOT NULL DEFAULT '',
  remind_at DATETIME NULL,
  is_done TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mcp_rem_user (user_id),
  CONSTRAINT fk_mcp_rem_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pengaturan default MCP (opt-in pembelian otomatis = nonaktif).
INSERT IGNORE INTO system_settings (`key`, `value`) VALUES
  ('mcp_enabled', '1'),
  ('mcp_tool_get_my_profile', '1'),
  ('mcp_tool_get_my_balance', '1'),
  ('mcp_tool_get_my_transaction_history', '1'),
  ('mcp_tool_get_my_transaction_status', '1'),
  ('mcp_tool_get_my_deposit_status', '1'),
  ('mcp_tool_search_ppob_products', '1'),
  ('mcp_tool_compare_ppob_products', '1'),
  ('mcp_tool_recommend_ppob_products', '1'),
  ('mcp_tool_get_ppob_product_details', '1'),
  ('mcp_tool_prepare_ppob_purchase', '1'),
  ('mcp_tool_execute_ppob_purchase', '1'),
  ('mcp_tool_get_purchase_policy', '1'),
  ('mcp_tool_get_my_spending_summary', '1'),
  ('mcp_tool_explain_my_transaction', '1'),
  ('mcp_tool_search_fanzpay_help', '1'),
  ('mcp_tool_create_customer_support_ticket', '1'),
  ('mcp_tool_get_fanzpay_promotions', '1'),
  ('mcp_tool_get_ppob_reminders', '1'),
  ('mcp_max_per_tx', '100000'),
  ('mcp_max_daily', '500000'),
  ('mcp_allowed_categories', 'pulsa,data,pln,emoney,game'),
  ('mcp_rate_per_min', '60'),
  ('mcp_prepare_ttl_minutes', '10');
