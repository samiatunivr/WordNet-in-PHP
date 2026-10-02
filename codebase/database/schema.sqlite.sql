-- Asl database schema (SQLite)
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(64) NULL,
    totp_last_step INTEGER NULL,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip VARCHAR(64) NOT NULL,
    email VARCHAR(190) NOT NULL,
    attempted_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email, attempted_at);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name_ar VARCHAR(200) NOT NULL,
    name_en VARCHAR(200) NOT NULL,
    name_nl VARCHAR(200) NOT NULL,
    summary_ar VARCHAR(500) NOT NULL DEFAULT '',
    summary_en VARCHAR(500) NOT NULL DEFAULT '',
    summary_nl VARCHAR(500) NOT NULL DEFAULT '',
    description_ar TEXT NOT NULL DEFAULT '',
    description_en TEXT NOT NULL DEFAULT '',
    description_nl TEXT NOT NULL DEFAULT '',
    price_per_kg_cents INTEGER NULL,
    price_per_l_cents INTEGER NULL,
    units VARCHAR(40) NOT NULL DEFAULT 'g,kg',
    is_active INTEGER NOT NULL DEFAULT 1,
    is_featured INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    filename VARCHAR(100) NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_product_images_product ON product_images(product_id, sort_order);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    public_id VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    locale VARCHAR(5) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    subtotal_cents INTEGER NOT NULL,
    shipping_cents INTEGER NOT NULL,
    total_cents INTEGER NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_name VARCHAR(190) NULL,
    customer_phone VARCHAR(60) NULL,
    shipping_address TEXT NULL,
    stripe_session_id VARCHAR(255) NULL UNIQUE,
    stripe_payment_intent VARCHAR(255) NULL,
    admin_note TEXT NOT NULL DEFAULT '',
    invoice_number VARCHAR(30) NULL UNIQUE,
    invoice_token VARCHAR(64) NULL,
    invoice_date DATETIME NULL,
    invoice_sent_at DATETIME NULL,
    vat_rate_bp INTEGER NULL,
    vat_cents INTEGER NULL,
    invoice_seller TEXT NULL,
    created_at DATETIME NOT NULL,
    paid_at DATETIME NULL,
    updated_at DATETIME NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status, created_at);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER NULL REFERENCES products(id) ON DELETE SET NULL,
    product_name VARCHAR(200) NOT NULL,
    unit VARCHAR(5) NOT NULL,
    quantity VARCHAR(30) NOT NULL,
    base_amount INTEGER NOT NULL,
    unit_price_cents INTEGER NOT NULL,
    line_total_cents INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS translations (
    tkey VARCHAR(120) NOT NULL PRIMARY KEY,
    ar TEXT NOT NULL DEFAULT '',
    en TEXT NOT NULL DEFAULT '',
    nl TEXT NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(80) NOT NULL PRIMARY KEY,
    svalue TEXT NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS stripe_events (
    event_id VARCHAR(255) NOT NULL PRIMARY KEY,
    received_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS counters (
    name VARCHAR(40) NOT NULL PRIMARY KEY,
    value INTEGER NOT NULL
);
