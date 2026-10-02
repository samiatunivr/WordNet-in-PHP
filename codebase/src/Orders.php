<?php
declare(strict_types=1);

namespace Asl;

final class Orders
{
    public const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'failed', 'review'];

    /** Create a pending order from a resolved cart. Returns the order row. */
    public static function createFromCart(array $cart, string $locale): array
    {
        return Db::transaction(static function () use ($cart, $locale) {
            $now = Db::now();
            $publicId = Security::randomId(12);
            $orderId = Db::insert('orders', [
                'public_id' => $publicId,
                'status' => 'pending',
                'locale' => $locale,
                'currency' => Config::currency(),
                'subtotal_cents' => $cart['subtotal'],
                'shipping_cents' => $cart['shipping'],
                'total_cents' => $cart['total'],
                'admin_note' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($cart['lines'] as $line) {
                Db::insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => (int) $line['product']['id'],
                    'product_name' => I18n::field($line['product'], 'name', $locale),
                    'unit' => $line['unit'],
                    'quantity' => $line['qty'],
                    'base_amount' => $line['base'],
                    'unit_price_cents' => $line['unit_price'],
                    'line_total_cents' => $line['line_total'],
                ]);
            }
            return Db::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        });
    }

    public static function items(int $orderId): array
    {
        return Db::all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    /**
     * Apply a Stripe Checkout Session to its order. Used by the webhook and as
     * a fallback on the success page (always with data fetched from Stripe,
     * never from the browser).
     */
    public static function syncFromSession(array $session): ?array
    {
        $sessionId = (string) ($session['id'] ?? '');
        $order = Db::one('SELECT * FROM orders WHERE stripe_session_id = ?', [$sessionId]);
        if (!$order) {
            return null;
        }
        if (($session['client_reference_id'] ?? null) !== $order['public_id']) {
            self::log('Session/order reference mismatch for ' . $sessionId);
            return $order;
        }

        $details = $session['customer_details'] ?? [];
        $shipping = $session['collected_information']['shipping_details'] ?? $session['shipping_details'] ?? null;
        $update = [
            'customer_email' => self::clip($details['email'] ?? $order['customer_email'], 190),
            'customer_name' => self::clip($shipping['name'] ?? $details['name'] ?? $order['customer_name'], 190),
            'customer_phone' => self::clip($details['phone'] ?? $order['customer_phone'], 60),
            'stripe_payment_intent' => is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : $order['stripe_payment_intent'],
            'updated_at' => Db::now(),
        ];
        if (is_array($shipping['address'] ?? null)) {
            $update['shipping_address'] = json_encode($shipping['address'], JSON_UNESCAPED_UNICODE);
        }

        $paymentStatus = $session['payment_status'] ?? '';
        if ($order['status'] === 'pending' && $paymentStatus === 'paid') {
            $amountOk = (int) ($session['amount_total'] ?? -1) === (int) $order['total_cents']
                && strtolower((string) ($session['currency'] ?? '')) === $order['currency'];
            $update['status'] = $amountOk ? 'paid' : 'review';
            $update['paid_at'] = Db::now();
            if (!$amountOk) {
                $update['admin_note'] = trim($order['admin_note'] . "\nAmount/currency mismatch reported by Stripe: "
                    . ($session['amount_total'] ?? '?') . ' ' . ($session['currency'] ?? '?'));
            }
        }
        Db::update('orders', $update, 'id = :id', ['id' => $order['id']]);
        return Db::one('SELECT * FROM orders WHERE id = ?', [$order['id']]);
    }

    public static function setStatusBySession(string $sessionId, string $from, string $to): void
    {
        Db::run('UPDATE orders SET status = ?, updated_at = ? WHERE stripe_session_id = ? AND status = ?', [$to, Db::now(), $sessionId, $from]);
    }

    private static function clip(mixed $v, int $len): ?string
    {
        return is_string($v) ? mb_substr($v, 0, $len) : null;
    }

    public static function log(string $msg): void
    {
        error_log('[asl] ' . $msg);
    }
}
