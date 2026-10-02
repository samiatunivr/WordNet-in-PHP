<?php
declare(strict_types=1);

namespace Asl;

/**
 * Session hardening, security headers and CSRF protection.
 */
final class Security
{
    public static function sendHeaders(): void
    {
        header_remove('X-Powered-By');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; "
            . "img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; "
            . "base-uri 'none'; frame-ancestors 'none'; form-action 'self' https://checkout.stripe.com"
            . (Config::isHttps() ? '; upgrade-insecure-requests' : ''));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        if (Config::isHttps()) {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
        }
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = Config::isHttps();
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        ini_set('session.gc_maxlifetime', '86400');
        session_name($secure ? '__Host-asl' : 'asl');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            // Lax keeps the cart when the customer returns from Stripe Checkout.
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    /** Verify CSRF token and same-origin for every state-changing request. */
    public static function verifyPost(): void
    {
        $token = $_POST['_csrf'] ?? '';
        $valid = is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && $origin !== 'null') {
            $valid = $valid && self::sameOrigin($origin);
        }
        if (!$valid) {
            Http::abort(419);
        }
    }

    private static function sameOrigin(string $origin): bool
    {
        $app = parse_url(Config::appUrl());
        $o = parse_url($origin);
        if (!$app || !$o) {
            return false;
        }
        return ($app['scheme'] ?? '') === ($o['scheme'] ?? '')
            && strtolower($app['host'] ?? '') === strtolower($o['host'] ?? '')
            && ($app['port'] ?? null) === ($o['port'] ?? null);
    }

    public static function randomId(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** One-shot message shown on the next page view. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function takeFlashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($f) ? $f : [];
    }
}
