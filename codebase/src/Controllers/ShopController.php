<?php
declare(strict_types=1);

namespace Asl\Controllers;

use Asl\Cart;
use Asl\Config;
use Asl\Db;
use Asl\Http;
use Asl\I18n;
use Asl\Orders;
use Asl\Products;
use Asl\Security;
use Asl\Settings;
use Asl\Stripe;
use Asl\Units;
use Asl\View;

final class ShopController
{
    /** Stripe's minimum charge for EUR is 0.50. */
    private const MIN_TOTAL_CENTS = 50;

    public function __construct(private string $locale)
    {
    }

    private function boot(): void
    {
        I18n::setLocale($this->locale);
    }

    public function home(): void
    {
        $this->boot();
        View::render('shop/home', ['title' => t('home.title'), 'products' => Products::active()]);
    }

    public function product(string $slug): void
    {
        $this->boot();
        $p = Products::findActiveBySlug($slug);
        if (!$p) {
            Http::abort(404);
        }
        View::render('shop/product', [
            'title' => I18n::field($p, 'name'),
            'product' => $p,
            'images' => Products::images((int) $p['id']),
            'units' => Units::forProduct($p),
        ]);
    }

    public function cart(): void
    {
        $this->boot();
        View::render('shop/cart', ['title' => t('cart.title'), 'cart' => Cart::resolve()]);
    }

    public function cartAdd(): void
    {
        $this->boot();
        $id = (int) Http::post('product_id');
        $unit = Http::post('unit');
        $p = $id > 0 ? Db::one('SELECT * FROM products WHERE id = ? AND is_active = 1', [$id]) : null;
        if (!$p) {
            Http::abort(404);
        }
        $back = url('product/' . rawurlencode($p['slug']));
        if (!in_array($unit, Units::forProduct($p), true)) {
            Security::flash('error', t('cart.invalid_unit'));
            Http::redirect($back);
        }
        $base = Units::toBase(Http::post('quantity'), $unit);
        $price = $base !== null ? Units::linePrice($p, $unit, $base) : null;
        if ($base === null || $price === null || $price < 1) {
            Security::flash('error', t('cart.invalid_quantity'));
            Http::redirect($back);
        }
        if (!Cart::add($id, $unit, $base)) {
            Security::flash('error', t('cart.limit'));
            Http::redirect($back);
        }
        Security::flash('success', t('cart.added'));
        Http::redirect(Http::post('go') === 'cart' ? url('cart') : $back);
    }

    public function cartUpdate(): void
    {
        $this->boot();
        $raw = Cart::raw();
        $key = Http::post('key');
        if (!isset($raw[$key])) {
            Http::redirect(url('cart'));
        }
        $unit = $raw[$key]['u'];
        $qty = Http::post('quantity');
        if (preg_match('/^0+([.,]0*)?$/', Units::normaliseDigits($qty))) {
            Cart::remove($key);
        } else {
            $base = Units::toBase($qty, $unit);
            $p = Products::find((int) $raw[$key]['p']);
            $price = ($base !== null && $p) ? Units::linePrice($p, $unit, $base) : null;
            if ($base === null || $price === null || $price < 1) {
                Security::flash('error', t('cart.invalid_quantity'));
            } else {
                Cart::set($key, $base);
                Security::flash('success', t('cart.updated'));
            }
        }
        Http::redirect(url('cart'));
    }

    public function cartRemove(): void
    {
        $this->boot();
        Cart::remove(Http::post('key'));
        Http::redirect(url('cart'));
    }

    public function checkout(): void
    {
        $this->boot();
        $cart = Cart::resolve();
        if (!$cart['lines']) {
            Http::redirect(url('cart'));
        }
        if ($cart['total'] < self::MIN_TOTAL_CENTS) {
            Security::flash('error', t('checkout.minimum', ['amount' => money(self::MIN_TOTAL_CENTS)]));
            Http::redirect(url('cart'));
        }

        $order = Orders::createFromCart($cart, $this->locale);
        $currency = Config::currency();
        $base = Config::appUrl() . url('');

        $lineItems = [];
        foreach ($cart['lines'] as $line) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $line['line_total'],
                    'product_data' => [
                        'name' => mb_substr(I18n::field($line['product'], 'name') . ' — ' . $line['qty'] . ' ' . Units::label($line['unit']), 0, 250),
                    ],
                ],
            ];
        }
        $params = [
            'mode' => 'payment',
            'client_reference_id' => $order['public_id'],
            'metadata' => ['order' => $order['public_id']],
            'payment_intent_data' => ['metadata' => ['order' => $order['public_id']]],
            'line_items' => $lineItems,
            'locale' => Stripe::checkoutLocale($this->locale),
            'success_url' => $base . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $base . '/checkout/cancel',
            'phone_number_collection' => ['enabled' => 'true'],
            'shipping_address_collection' => ['allowed_countries' => Settings::countries() ?: ['NL']],
            'shipping_options' => [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'display_name' => mb_substr(t('checkout.shipping_name'), 0, 100),
                    'fixed_amount' => ['amount' => $cart['shipping'], 'currency' => $currency],
                ],
            ]],
        ];

        try {
            $session = Stripe::request('POST', 'checkout/sessions', $params, 'order-' . $order['public_id']);
        } catch (\Throwable $e) {
            Orders::log('Checkout failed for ' . $order['public_id'] . ': ' . $e->getMessage());
            Db::run('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', ['failed', Db::now(), $order['id']]);
            Security::flash('error', t('checkout.error'));
            Http::redirect(url('cart'));
        }

        $sessionUrl = (string) ($session['url'] ?? '');
        Db::run('UPDATE orders SET stripe_session_id = ?, updated_at = ? WHERE id = ?', [$session['id'], Db::now(), $order['id']]);
        $_SESSION['orders'] = array_slice(array_merge($_SESSION['orders'] ?? [], [$order['public_id']]), -20);
        if (!str_starts_with($sessionUrl, 'https://checkout.stripe.com/')) {
            Http::abort(500);
        }
        Http::redirect($sessionUrl);
    }

    public function success(): void
    {
        $this->boot();
        $sessionId = Http::query('session_id');
        if (!preg_match('/^cs_(test|live)_[A-Za-z0-9]{10,200}$/', $sessionId)) {
            Http::abort(404);
        }
        $order = Db::one('SELECT * FROM orders WHERE stripe_session_id = ?', [$sessionId]);
        // Only the browser that placed the order may view it.
        if (!$order || !in_array($order['public_id'], $_SESSION['orders'] ?? [], true)) {
            Http::abort(404);
        }
        if ($order['status'] === 'pending') {
            try {
                $order = Orders::syncFromSession(Stripe::request('GET', 'checkout/sessions/' . $sessionId)) ?? $order;
            } catch (\Throwable $e) {
                Orders::log('Success-page sync failed: ' . $e->getMessage());
            }
        }
        Cart::clear();
        View::render('shop/success', [
            'title' => t('success.title'),
            'order' => $order,
            'items' => Orders::items((int) $order['id']),
        ]);
    }

    public function cancel(): void
    {
        $this->boot();
        Security::flash('info', t('checkout.cancelled'));
        Http::redirect(url('cart'));
    }
}
