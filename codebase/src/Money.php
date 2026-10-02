<?php
declare(strict_types=1);

namespace Asl;

final class Money
{
    private const FORMAT_LOCALES = ['ar' => 'ar', 'en' => 'en-IE', 'nl' => 'nl-NL'];

    public static function format(int $cents, ?string $locale = null): string
    {
        $locale ??= I18n::locale();
        $currency = strtoupper(Config::currency());
        if (class_exists(\NumberFormatter::class)) {
            $fmt = new \NumberFormatter(self::FORMAT_LOCALES[$locale] ?? 'en', \NumberFormatter::CURRENCY);
            $out = $fmt->formatCurrency($cents / 100, $currency);
            if ($out !== false) {
                return $out;
            }
        }
        return $currency . ' ' . number_format($cents / 100, 2, '.', ',');
    }

    /** Parse an admin-entered price such as "45", "45.50" or "45,50" into cents. */
    public static function parse(string $input): ?int
    {
        $input = str_replace([',', ' '], ['.', ''], Units::normaliseDigits(trim($input)));
        if ($input === '') {
            return null;
        }
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $input)) {
            return -1;
        }
        [$int, $frac] = array_pad(explode('.', $input), 2, '');
        return (int) $int * 100 + (int) str_pad($frac, 2, '0');
    }

    public static function toInput(?int $cents): string
    {
        return $cents === null ? '' : number_format($cents / 100, 2, '.', '');
    }
}
