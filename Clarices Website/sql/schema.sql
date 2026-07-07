-- ============================================================
-- INJILI APPAREL — Database Schema
-- Engine: MySQL 5.7+ / MariaDB 10.4+
-- Run once in phpMyAdmin: create database injili_db first,
-- then import this file.
-- ============================================================

CREATE DATABASE IF NOT EXISTS injili_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE injili_db;

-- ─────────────────────────────────────────
-- TABLE: admins
-- Clarice's login. Kept separate from users
-- so a compromised customer session can never
-- escalate to admin privileges.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)  NOT NULL,
  email       VARCHAR(191)  NOT NULL UNIQUE,
  -- password stored as bcrypt hash (password_hash / PASSWORD_BCRYPT)
  password    VARCHAR(255)  NOT NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin seed (password: admin1234 — change before going live!)
-- Hash generated with: password_hash('admin1234', PASSWORD_BCRYPT)
INSERT INTO admins (name, email, password) VALUES
  ('Clarice', 'clarice@injiliapparel.com',
   '$2y$12$YQm5YtHH5Z8kHfQn1W4P8OVbG6bKPUgKTjN0eSq/yHADkFP5WRiR2');


-- ─────────────────────────────────────────
-- TABLE: users
-- Customer accounts. user_id is nullable on
-- orders so guest checkout is supported.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)   NOT NULL,
  last_name     VARCHAR(80)   NOT NULL,
  email         VARCHAR(191)  NOT NULL UNIQUE,
  phone         VARCHAR(20)   NOT NULL,
  -- bcrypt hash
  password      VARCHAR(255)  NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: products
-- Parent product (the design). Colour and
-- size options live in product_variants so
-- Clarice can add new colour options without
-- touching this table.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120)  NOT NULL,
  -- scripture reference displayed as subtitle
  verse       VARCHAR(100)  NOT NULL,
  -- short marketing tag shown on card badge
  tag         VARCHAR(60)   NOT NULL DEFAULT 'New Drop',
  -- editorial description paragraph
  description TEXT          NOT NULL,
  -- material and fit copy for accordion
  material    TEXT          NOT NULL,
  fit         TEXT          NOT NULL,
  -- price in Kenya Shillings (no decimals needed)
  price_ksh   INT UNSIGNED  NOT NULL,
  -- CSS class name for the tee colour mock-up (tee-black, tee-white, etc.)
  tee_class   VARCHAR(40)   NOT NULL DEFAULT 'tee-black',
  -- hex code for the card background
  bg_color    VARCHAR(10)   NOT NULL DEFAULT '#1C2329',
  -- raw HTML for the graphic overlay on the CSS tee mockup
  graphic_html MEDIUMTEXT   NOT NULL DEFAULT '',
  -- soft delete: set to 0 to hide from shop without losing order history
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order  SMALLINT      NOT NULL DEFAULT 0,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: product_variants
-- Each row = one colour option for a product.
-- Sizes are NOT rows — the business is made-to-
-- order so there is no per-size inventory.
-- Sizes are stored as a JSON array for flexibility.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_variants (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED  NOT NULL,
  color_name  VARCHAR(60)   NOT NULL,
  -- hex value used to render the colour swatch dot
  color_hex   VARCHAR(10)   NOT NULL,
  -- available sizes as JSON array, e.g. ["S","M","L","XL","XXL"]
  sizes       JSON          NOT NULL,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order  SMALLINT      NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: orders
-- One row per checkout. Holds the customer's
-- contact details (denormalised) so we retain
-- the exact info used at order time even if
-- the customer later updates their profile.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- human-readable reference, e.g. INJ-2025-0001
  order_number        VARCHAR(20)   NOT NULL UNIQUE,
  -- nullable for guest checkout
  user_id             INT UNSIGNED  NULL,
  -- ── contact info (captured at checkout) ──
  customer_name       VARCHAR(160)  NOT NULL,
  customer_email      VARCHAR(191)  NOT NULL,
  customer_phone      VARCHAR(20)   NOT NULL,
  -- ── delivery ──
  -- 'agent_pickup' | 'doorstep' | 'errand'
  delivery_mode       ENUM('agent_pickup','doorstep','errand') NOT NULL DEFAULT 'doorstep',
  delivery_address    TEXT          NULL,
  delivery_city       VARCHAR(80)   NULL,
  delivery_area       VARCHAR(80)   NULL,
  delivery_notes      TEXT          NULL,
  delivery_fee_ksh    INT UNSIGNED  NOT NULL DEFAULT 0,
  -- ── pricing ──
  subtotal_ksh        INT UNSIGNED  NOT NULL,
  total_ksh           INT UNSIGNED  NOT NULL,
  -- ── order lifecycle ──
  -- matches order_status_history.status values
  status              ENUM(
                        'received',
                        'in_production',
                        'ready_for_dispatch',
                        'handed_to_pm',
                        'in_transit',
                        'out_for_delivery',
                        'delivered'
                      ) NOT NULL DEFAULT 'received',
  -- ── payment ──
  -- 'pending' → 'paid' | 'failed' | 'manual'
  payment_status      ENUM('pending','paid','failed','manual') NOT NULL DEFAULT 'pending',
  payment_method      ENUM('mpesa','manual') NOT NULL DEFAULT 'mpesa',
  -- M-Pesa CheckoutRequestID returned by STK push
  mpesa_checkout_id   VARCHAR(100)  NULL,
  -- M-Pesa transaction receipt, e.g. RGQ8H3KFQL
  mpesa_receipt       VARCHAR(20)   NULL,
  created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: order_items
-- One row per line item (design + colour + size + qty).
-- Prices are snapshotted at order time so a
-- future price change does not alter history.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id        INT UNSIGNED  NOT NULL,
  product_id      INT UNSIGNED  NOT NULL,
  variant_id      INT UNSIGNED  NOT NULL,
  -- snapshot of names in case product is later renamed
  product_name    VARCHAR(120)  NOT NULL,
  color_name      VARCHAR(60)   NOT NULL,
  size            VARCHAR(10)   NOT NULL,
  quantity        SMALLINT      NOT NULL DEFAULT 1,
  unit_price_ksh  INT UNSIGNED  NOT NULL,
  line_total_ksh  INT UNSIGNED  NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)           ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)         ON DELETE RESTRICT,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: order_status_history
-- Append-only audit trail. One insert per
-- status change. Never updated or deleted.
-- This drives the customer tracking timeline.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_status_history (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED  NOT NULL,
  -- matches orders.status ENUM
  status      VARCHAR(40)   NOT NULL,
  -- optional note from Clarice, e.g. "Handed to PM agent Westlands"
  note        TEXT          NULL,
  -- 'admin' | 'system' | 'mpesa_callback'
  changed_by  VARCHAR(40)   NOT NULL DEFAULT 'system',
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: delivery_tracking
-- Mirrors the Pickup Mtaani tracking payload.
-- Written by pickupmtaani.php (mock or live).
-- One row per order — updated in place as the
-- parcel moves through PM's network.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_tracking (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id        INT UNSIGNED  NOT NULL UNIQUE,
  -- PM's reference number, e.g. PM-2025-001234
  pm_reference    VARCHAR(60)   NOT NULL,
  -- current status label from PM
  pm_status       VARCHAR(80)   NOT NULL DEFAULT 'Parcel Created',
  -- full PM event history as JSON array of {status, timestamp, note}
  pm_events       JSON          NOT NULL DEFAULT (JSON_ARRAY()),
  -- agent info for agent_pickup orders
  agent_name      VARCHAR(120)  NULL,
  agent_location  VARCHAR(200)  NULL,
  agent_phone     VARCHAR(20)   NULL,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: notifications_log
-- Every attempted notification is logged here
-- with outcome. Clarice can see in the admin
-- if an SMS or email failed and resend manually.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED  NOT NULL,
  -- 'email' | 'sms' | 'whatsapp'
  channel     ENUM('email','sms','whatsapp') NOT NULL,
  -- 'customer' | 'admin'
  recipient   ENUM('customer','admin')       NOT NULL,
  -- the address/number that was targeted
  destination VARCHAR(191)  NOT NULL,
  event       VARCHAR(80)   NOT NULL,
  -- 'sent' | 'failed'
  status      ENUM('sent','failed')          NOT NULL DEFAULT 'sent',
  -- any error message if status = 'failed'
  error_msg   TEXT          NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- TABLE: newsletter_subscribers
-- Stores email sign-ups from the homepage form.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(191)  NOT NULL UNIQUE,
  subscribed_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────
-- SEED: Genesis Collection products
-- Mirrors the 4 products in the existing JS.
-- Clarice can edit/add more via admin dashboard.
-- ─────────────────────────────────────────
INSERT INTO products
  (name, verse, tag, description, material, fit, price_ksh, tee_class, bg_color, graphic_html, sort_order)
VALUES
(
  'In The Beginning', 'Genesis 1:1', 'New Drop',
  'A bold declaration of origins. Set in our signature editorial typeface with gold flourishes on premium heavyweight cotton.',
  '240gsm ring-spun cotton, pre-shrunk. Printed with water-based inks for softness and longevity.',
  'Relaxed unisex fit. Model is 6\'1" wearing size L. We recommend sizing up for an oversized look.',
  3800, 'tee-black', '#1C2329',
  '<span style="font-family:var(--fd);font-size:.5rem;color:var(--gold);letter-spacing:.2em;text-transform:uppercase;display:block;margin-bottom:3px">Genesis</span><span style="font-family:var(--fd);font-size:.9rem;font-weight:300;font-style:italic;color:var(--white);line-height:1;display:block">In The<br>Beginning</span><span style="font-family:var(--fb);font-size:.38rem;color:rgba(255,255,255,.3);letter-spacing:.2em;text-transform:uppercase;margin-top:4px;display:block">1:1</span>',
  1
),
(
  'Light Carrier', 'John 8:12', 'Bestseller',
  'A minimal cross motif on premium cotton — quiet enough for everyday wear, intentional enough to start a conversation.',
  '240gsm combed cotton, pre-shrunk. Mineral-washed for a lived-in, premium feel.',
  'Relaxed unisex fit. Runs true to size. Model is 5\'8" wearing size M.',
  3800, 'tee-white', '#E8E5E0',
  '<div style="width:28px;height:28px;margin:0 auto 4px;position:relative"><div style="position:absolute;width:2px;height:28px;background:var(--gold);left:50%;transform:translateX(-50%)"></div><div style="position:absolute;width:18px;height:2px;background:var(--gold);top:38%;left:50%;transform:translate(-50%,-50%)"></div></div><span style="font-family:var(--fb);font-size:.42rem;color:rgba(0,0,0,.35);letter-spacing:.25em;text-transform:uppercase;margin-top:5px;display:block">Light Carrier</span>',
  2
),
(
  'The Garden', 'Genesis 2:8', 'Premium',
  'An organic circular motif echoing the perfection of Eden. Our most detailed design, screen-printed in four layers.',
  '280gsm heavyweight cotton, enzyme-washed. Screen-printed with four-colour overlay.',
  'Relaxed boxy fit. Model is 6\'0" wearing size M. Recommend true to size or size down.',
  4200, 'tee-olive', '#2A2A2A',
  '<div style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(201,138,65,.5);margin:0 auto 4px;display:flex;align-items:center;justify-content:center"><div style="width:10px;height:10px;border-radius:50%;background:rgba(201,138,65,.4)"></div></div><span style="font-family:var(--fd);font-size:.6rem;font-style:italic;color:rgba(255,255,255,.7);letter-spacing:.05em">The Garden</span>',
  3
),
(
  'Seventh Day', 'Genesis 2:2–3', 'Almost Gone',
  'The quietest piece in the collection. Roman numerals VII centred on stone — a reminder to rest with intention.',
  '240gsm French terry cotton. Ultra-soft interior finish. Garment-dyed for a faded, premium look.',
  'Relaxed standard fit. Runs slightly large — true to size recommended.',
  3800, 'tee-cream', '#C5BFB8',
  '<span style="font-family:var(--fb);font-size:.38rem;letter-spacing:.3em;text-transform:uppercase;color:rgba(0,0,0,.4);margin-bottom:3px;display:block">Seventh</span><span style="font-family:var(--fd);font-size:1.1rem;font-weight:300;color:rgba(0,0,0,.7);line-height:1;margin-bottom:3px;display:block">VII</span><span style="font-family:var(--fb);font-size:.38rem;letter-spacing:.3em;text-transform:uppercase;color:rgba(0,0,0,.4);display:block">Day</span>',
  4
);

-- Seed product variants (colours per design)
-- Product 1: In The Beginning → Black, White
INSERT INTO product_variants (product_id, color_name, color_hex, sizes, sort_order) VALUES
  (1, 'Black',   '#0d1117', '["S","M","L","XL","XXL"]', 1),
  (1, 'White',   '#F8F8F6', '["S","M","L","XL","XXL"]', 2);

-- Product 2: Light Carrier → White, Natural
INSERT INTO product_variants (product_id, color_name, color_hex, sizes, sort_order) VALUES
  (2, 'White',   '#F8F8F6', '["S","M","L","XL","XXL"]', 1),
  (2, 'Natural', '#EDE8E0', '["S","M","L","XL","XXL"]', 2);

-- Product 3: The Garden → Olive, Charcoal
INSERT INTO product_variants (product_id, color_name, color_hex, sizes, sort_order) VALUES
  (3, 'Olive',    '#4A4A3A', '["S","M","L","XL","XXL"]', 1),
  (3, 'Charcoal', '#2A2A2A', '["S","M","L","XL","XXL"]', 2);

-- Product 4: Seventh Day → Sand only
INSERT INTO product_variants (product_id, color_name, color_hex, sizes, sort_order) VALUES
  (4, 'Sand', '#EDE8E0', '["S","M","L","XL","XXL"]', 1);
