<?php
declare(strict_types=1);

namespace Asl;

/**
 * Session cart. Only product id, unit and quantity (in base units) are kept;
 * names and prices are always re-read from the database so a tampered
 * request can never change what the customer pays.
 */
final class Cart
{
    public const MAX_LINES = 50;

    private static function &items(): array
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        return $_SESSION['cart'];
    }

    public static function add(int $productId, string $unit, int $base): bool
    {
        $items = &self::items();
        $key = $productId . ':' . $unit;
        if (!isset($items[$key]) && count($items) >= self::MAX_LINES) {
            return false;
        }
        $current = $items[$key]['b'] ?? 0;
        $new = $current + $base;
        if ($new > Units::MAX_BASE[Units::dimension($unit)]) {
            return false;
        }
        $items[$key] = ['p' => $productId, 'u' => $unit, 'b' => $new];
        return true;
    }

    public static function set(string $key, int $base): void
    {
        $items = &self::items();
        if (isset($items[$key])) {
            $items[$key]['b'] = $base;
        }
    }

    public static function remove(string $key): void
    {
        $items = &self::items();
        unset($items[$key]);
    }

    public static function clear(): void
    {
        $_SESSION['cart'] = [];
    }

    public static function count(): int
    {
        return count(self::items());
    }

    public static function raw(): array
    {
        return self::items();
    }

    /**
     * Resolve the cart against the database. Lines whose product was removed,
     * disabled, or whose unit is no longer sold are dropped.
     *
     * @return array{lines: array, subtotal: int, shipping: int, total: int}
     */
    public static function resolve(): array
    {
        $items = &self::items();
        $lines = [];
        $subtotal = 0;
        foreach ($items as $key => $item) {
            $p = Db::one('SELECT * FROM products WHERE id = ? AND is_active = 1', [(int) ($item['p'] ?? 0)]);
            $unit = (string) ($item['u'] ?? '');
            $base = (int) ($item['b'] ?? 0);
            if (!$p || !in_array($unit, Units::forProduct($p), true) || $base < 1) {
                unset($items[$key]);
                continue;
            }
            $price = Units::linePrice($p, $unit, $base);
            if ($price === null || $price < 1) {
                unset($items[$key]);
                continue;
            }
            $images = Products::images((int) $p['id']);
            $lines[] = [
                'key' => $key,
                'product' => $p,
                'unit' => $unit,
                'base' => $base,
                'qty' => Units::fromBase($base, $unit),
                'unit_price' => Units::dimension($unit) === 'mass' ? (int) $p['price_per_kg_cents'] : (int) $p['price_per_l_cents'],
                'line_total' => $price,
                'image' => $images[0]['filename'] ?? null,
            ];
            $subtotal += $price;
        }
        $shipping = $lines ? Settings::shippingFor($subtotal) : 0;
        return ['lines' => $lines, 'subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping];
    }
}
