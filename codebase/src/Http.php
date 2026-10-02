<?php
declare(strict_types=1);

namespace Asl;

final class Http
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';
        $path = '/' . trim($path, '/');
        return $path;
    }

    public static function post(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    public static function query(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Client IP, honouring X-Forwarded-For only from configured trusted proxies. */
    public static function ip(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $trusted = (array) Config::get('trusted_proxies', []);
        if ($trusted && in_array($remote, $trusted, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $chain = array_reverse(array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])));
            foreach ($chain as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $trusted, true)) {
                    return $ip;
                }
            }
        }
        return $remote;
    }

    public static function redirect(string $url, int $code = 303): never
    {
        // Only allow local paths or our own app URL / Stripe Checkout.
        $isLocal = str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, '\\');
        $isStripe = str_starts_with($url, 'https://checkout.stripe.com/');
        if (!$isLocal && !$isStripe) {
            $url = '/';
        }
        header('Location: ' . $url, true, $code);
        exit;
    }

    public static function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        View::error($code, $message);
        exit;
    }

    public static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
