<?php
declare(strict_types=1);

namespace Asl;

/**
 * Minimal Stripe REST client (no SDK dependency). Card data never touches
 * this server: customers pay on Stripe-hosted Checkout.
 */
final class Stripe
{
    private const API = 'https://api.stripe.com/v1/';
    private const WEBHOOK_TOLERANCE = 300;

    public static function request(string $method, string $path, array $params = [], ?string $idempotencyKey = null): array
    {
        $key = (string) Config::get('stripe_secret_key', '');
        if ($key === '' || str_contains($key, 'xxx')) {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }
        $url = self::API . ltrim($path, '/');
        $body = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        if ($method === 'GET' && $body !== '') {
            $url .= '?' . $body;
        }
        $headers = ['Authorization: Bearer ' . $key, 'Content-Type: application/x-www-form-urlencoded'];
        if ($idempotencyKey !== null) {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new \RuntimeException('Stripe connection error: ' . $err);
        }
        $data = json_decode((string) $raw, true);
        if (!is_array($data) || $status >= 400) {
            $msg = is_array($data) ? ($data['error']['message'] ?? 'unknown') : 'invalid response';
            throw new \RuntimeException("Stripe API error ($status): $msg");
        }
        return $data;
    }

    /**
     * Verify the Stripe-Signature header (HMAC-SHA256 over "timestamp.payload")
     * and return the decoded event, or null if the signature is invalid.
     */
    public static function verifyWebhook(string $payload, string $header): ?array
    {
        $secret = (string) Config::get('stripe_webhook_secret', '');
        if ($secret === '' || $header === '') {
            return null;
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't' && ctype_digit($v)) {
                $timestamp = (int) $v;
            } elseif ($k === 'v1') {
                $signatures[] = $v;
            }
        }
        if ($timestamp === null || !$signatures || abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE) {
            return null;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                $event = json_decode($payload, true);
                return is_array($event) ? $event : null;
            }
        }
        return null;
    }

    /** Stripe Checkout does not offer an Arabic UI; let it auto-detect. */
    public static function checkoutLocale(string $locale): string
    {
        return in_array($locale, ['en', 'nl'], true) ? $locale : 'auto';
    }
}
