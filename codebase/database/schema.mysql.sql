-- Asl database schema (MySQL / MariaDB, utf8mb4)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(64) NULL,
    totp_last_step BIGINT NULL,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(64) NOT NULL,
    email VARCHAR(190) NOT NULL,
    attempted_at BIGINT NOT NULL,
    INDEX idx_login_attempts_ip (ip, attempted_at),
    INDEX idx_login_attempts_email (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name_ar VARCHAR(200) NOT NULL,
    name_en VARCHAR(200) NOT NULL,
    name_nl VARCHAR(200) NOT NULL,
    summary_ar VARCHAR(500) NOT NULL DEFAULT '',
    summary_en VARCHAR(500) NOT NULL DEFAULT '',
    summary_nl VARCHAR(500) NOT NULL DEFAULT '',
    description_ar TEXT NOT NULL,
    description_en TEXT NOT NULL,
    description_nl TEXT NOT NULL,
    price_per_kg_cents INT NULL,
    price_per_l_cents INT NULL,
    units VARCHAR(40) NOT NULL DEFAULT 'g,kg',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    filename VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_product_images_product (product_id, sort_order),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    locale VARCHAR(5) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    subtotal_cents INT NOT NULL,
    shipping_cents INT NOT NULL,
    total_cents INT NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_name VARCHAR(190) NULL,
    customer_phone VARCHAR(60) NULL,
    shipping_address TEXT NULL,
    stripe_session_id VARCHAR(255) NULL UNIQUE,
    stripe_payment_intent VARCHAR(255) NULL,
    admin_note TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    paid_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_orders_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    unit VARCHAR(5) NOT NULL,
    quantity VARCHAR(30) NOT NULL,
    base_amount BIGINT NOT NULL,
    unit_price_cents INT NOT NULL,
    line_total_cents INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translations (
    tkey VARCHAR(120) NOT NULL PRIMARY KEY,
    ar TEXT NOT NULL,
    en TEXT NOT NULL,
    nl TEXT NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(80) NOT NULL PRIMARY KEY,
    svalue TEXT NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_events (
    event_id VARCHAR(255) NOT NULL PRIMARY KEY,
    received_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
