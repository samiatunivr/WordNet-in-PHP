<?php
declare(strict_types=1);

namespace Asl;

final class Settings
{
    public const DEFAULTS = [
        'shipping_flat_cents' => '695',
        'free_shipping_from_cents' => '7500',
        'shipping_countries' => 'NL,BE,DE,FR,LU,AT',
        'contact_email' => '',
        'contact_phone' => '',
    ];

    private static ?array $cache = null;

    public static function get(string $key): string
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Db::all('SELECT skey, svalue FROM settings') as $row) {
                self::$cache[$row['skey']] = $row['svalue'];
            }
        }
        return self::$cache[$key] ?? self::DEFAULTS[$key] ?? '';
    }

    public static function set(string $key, string $value): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException('Unknown setting');
        }
        $exists = Db::value('SELECT COUNT(*) FROM settings WHERE skey = ?', [$key]);
        if ((int) $exists > 0) {
            Db::run('UPDATE settings SET svalue = ?, updated_at = ? WHERE skey = ?', [$value, Db::now(), $key]);
        } else {
            Db::insert('settings', ['skey' => $key, 'svalue' => $value, 'updated_at' => Db::now()]);
        }
        self::$cache = null;
    }

    public static function countries(): array
    {
        $list = array_filter(array_map('trim', explode(',', strtoupper(self::get('shipping_countries')))));
        return array_values(array_filter($list, static fn ($c) => preg_match('/^[A-Z]{2}$/', $c) === 1));
    }

    public static function shippingFor(int $subtotal): int
    {
        $free = (int) self::get('free_shipping_from_cents');
        if ($free > 0 && $subtotal >= $free) {
            return 0;
        }
        return max(0, (int) self::get('shipping_flat_cents'));
    }
}
