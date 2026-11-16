-- HOME TASTE FOODS - Supabase (PostgreSQL) schema
-- Run this in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS rpos_admin (
  admin_id VARCHAR(200) PRIMARY KEY,
  admin_name VARCHAR(200) NOT NULL,
  admin_email VARCHAR(200) NOT NULL,
  admin_password VARCHAR(200) NOT NULL
);

CREATE TABLE IF NOT EXISTS rpos_customers (
  customer_id VARCHAR(200) PRIMARY KEY,
  customer_name VARCHAR(200) NOT NULL,
  customer_phoneno VARCHAR(200) NOT NULL,
  customer_email VARCHAR(200) NOT NULL,
  customer_password VARCHAR(200) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS rpos_staff (
  staff_id VARCHAR(200) PRIMARY KEY,
  staff_name VARCHAR(200) NOT NULL,
  staff_number VARCHAR(200),
  staff_email VARCHAR(200) NOT NULL,
  staff_password VARCHAR(200) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS rpos_products (
  prod_id VARCHAR(200) PRIMARY KEY,
  prod_code VARCHAR(200),
  prod_name VARCHAR(200) NOT NULL,
  prod_img VARCHAR(500),
  prod_desc TEXT,
  prod_price VARCHAR(200) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS rpos_orders (
  order_id VARCHAR(200) PRIMARY KEY,
  order_code VARCHAR(200) NOT NULL,
  customer_id VARCHAR(200) NOT NULL,
  customer_name VARCHAR(200) NOT NULL,
  prod_id VARCHAR(200) NOT NULL,
  prod_name VARCHAR(200) NOT NULL,
  prod_price VARCHAR(200) NOT NULL,
  prod_qty VARCHAR(200) NOT NULL,
  order_status VARCHAR(200) NOT NULL DEFAULT 'Pending',
  order_type VARCHAR(50),
  packaging_fee DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_orders_code ON rpos_orders(order_code);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON rpos_orders(customer_id);

CREATE TABLE IF NOT EXISTS rpos_payments (
  pay_id VARCHAR(200) PRIMARY KEY,
  pay_code VARCHAR(200) NOT NULL,
  order_code VARCHAR(200) NOT NULL,
  customer_id VARCHAR(200) NOT NULL,
  pay_amt VARCHAR(200) NOT NULL,
  pay_method VARCHAR(200) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS rpos_pass_resets (
  reset_id VARCHAR(200) PRIMARY KEY,
  reset_code VARCHAR(200),
  reset_token VARCHAR(200),
  reset_email VARCHAR(200),
  reset_status VARCHAR(200),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Default admin (password: codeastro.com  → same hash as original)
INSERT INTO rpos_admin (admin_id, admin_name, admin_email, admin_password) VALUES
('10e0b6dc958adfb5b094d8935a13aeadbe783c25', 'System Admin', 'admin@mail.com', '903b21879b4a60fc9103c3334e4f6f62cf6c3a2d')
ON CONFLICT (admin_id) DO NOTHING;

-- Default cashier (need staff_id - generate)
INSERT INTO rpos_staff (staff_id, staff_name, staff_number, staff_email, staff_password) VALUES
('cashier001', 'Cashier', 'CSH-001', 'cashier@mail.com', '903b21879b4a60fc9103c3334e4f6f62cf6c3a2d')
ON CONFLICT (staff_id) DO NOTHING;
