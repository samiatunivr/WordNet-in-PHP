<?php
declare(strict_types=1);

namespace Asl;

/**
 * Quantity units. Mass is stored internally in milligrams, volume in
 * millilitres, so all arithmetic is exact integer math.
 *
 * Products carry a price per kilogram and/or per litre; the customer picks a
 * unit and a quantity and the server computes the line price.
 */
final class Units
{
    /** unit => [dimension, factor to base unit, allowed decimals] */
    public const UNITS = [
        'mg' => ['mass', 1, 0],
        'g'  => ['mass', 1000, 3],
        'kg' => ['mass', 1000000, 3],
        'ml' => ['volume', 1, 0],
        'l'  => ['volume', 1000, 3],
    ];

    /** Per-line ceilings: 100 kg / 100 L. */
    public const MAX_BASE = ['mass' => 100_000_000, 'volume' => 100_000];

    public static function isUnit(string $u): bool
    {
        return isset(self::UNITS[$u]);
    }

    public static function dimension(string $u): string
    {
        return self::UNITS[$u][0];
    }

    /** Units enabled for a product that also have a price set. */
    public static function forProduct(array $p): array
    {
        $out = [];
        foreach (explode(',', (string) $p['units']) as $u) {
            $u = trim($u);
            if (!self::isUnit($u)) {
                continue;
            }
            if (self::dimension($u) === 'mass' && $p['price_per_kg_cents'] !== null) {
                $out[] = $u;
            } elseif (self::dimension($u) === 'volume' && $p['price_per_l_cents'] !== null) {
                $out[] = $u;
            }
        }
        return $out;
    }

    /** Convert Arabic-Indic / Persian digits and the Arabic decimal mark to ASCII. */
    public static function normaliseDigits(string $s): string
    {
        $map = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => '',
        ];
        return strtr($s, $map);
    }

    /**
     * Parse a customer quantity like "250", "1.5" or "1,5" in the given unit
     * into base units (mg or ml). Returns null when invalid or out of range.
     */
    public static function toBase(string $qty, string $unit): ?int
    {
        if (!self::isUnit($unit)) {
            return null;
        }
        [$dim, $factor, $decimals] = self::UNITS[$unit];
        $qty = str_replace(',', '.', self::normaliseDigits(trim($qty)));
        $pattern = $decimals > 0 ? '/^\d{1,9}(\.\d{1,' . $decimals . '})?$/' : '/^\d{1,9}$/';
        if (!preg_match($pattern, $qty)) {
            return null;
        }
        [$int, $frac] = array_pad(explode('.', $qty), 2, '');
        $base = (int) $int * $factor;
        if ($frac !== '') {
            $base += intdiv((int) str_pad($frac, $decimals, '0') * $factor, 10 ** $decimals);
        }
        if ($base < 1 || $base > self::MAX_BASE[$dim]) {
            return null;
        }
        return $base;
    }

    /** Human quantity in the chosen unit, e.g. 1500000 mg in kg => "1.5" */
    public static function fromBase(int $base, string $unit): string
    {
        [, $factor, $decimals] = self::UNITS[$unit];
        $s = number_format($base / $factor, $decimals, '.', '');
        return $decimals > 0 ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    /** Price in cents for $base mg/ml of a product. Rounded half up. */
    public static function linePrice(array $p, string $unit, int $base): ?int
    {
        if (self::dimension($unit) === 'mass') {
            $price = $p['price_per_kg_cents'];
            $per = 1_000_000;
        } else {
            $price = $p['price_per_l_cents'];
            $per = 1000;
        }
        if ($price === null) {
            return null;
        }
        return intdiv($base * (int) $price + intdiv($per, 2), $per);
    }

    public static function label(string $unit): string
    {
        return I18n::t('unit.' . $unit);
    }
}
