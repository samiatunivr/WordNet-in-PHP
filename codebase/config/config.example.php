<?php
/**
 * Asl configuration.
 *
 * Copy this file to config.php (same folder) and fill in the values.
 * config.php is git-ignored and lives outside the public web root.
 * Every value can also be provided by an environment variable of the same
 * upper-cased name (e.g. STRIPE_SECRET_KEY), which takes precedence.
 */
return [
    // Absolute public URL of the shop, without trailing slash.
    'app_url' => 'https://asl.example.com',

    // 'production' disables error output and forces secure cookies.
    'app_env' => 'production',

    // PDO DSN. SQLite works out of the box; MySQL example:
    // 'mysql:host=127.0.0.1;dbname=asl;charset=utf8mb4'
    'db_dsn' => 'sqlite:' . __DIR__ . '/../storage/database/asl.sqlite',
    'db_user' => null,
    'db_pass' => null,

    // Stripe keys: https://dashboard.stripe.com/apikeys
    'stripe_secret_key' => 'sk_test_xxx',
    // Signing secret of the webhook endpoint pointing at {app_url}/stripe/webhook
    'stripe_webhook_secret' => 'whsec_xxx',
    // ISO currency code used for all prices.
    'currency' => 'eur',

    // URL path of the admin panel. Change it to something hard to guess.
    'admin_path' => 'admin',

    // Session idle timeout for the admin panel, in seconds.
    'admin_idle_timeout' => 1800,

    // Max upload size per image in bytes.
    'max_upload_bytes' => 5 * 1024 * 1024,

    // Behind a reverse proxy / load balancer that sets X-Forwarded-For,
    // list the proxy IPs here so the real client IP is used for rate limiting.
    'trusted_proxies' => [],
];
