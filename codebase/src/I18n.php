<?php
declare(strict_types=1);

namespace Asl;

/**
 * Three hand-written languages. There is no automatic translation: default
 * strings live in lang/{ar,en,nl}.php and every one of them can be
 * overridden per language from the admin panel (translations table).
 */
final class I18n
{
    public const LOCALES = ['ar', 'en', 'nl'];
    public const NAMES = ['ar' => 'العربية', 'en' => 'English', 'nl' => 'Nederlands'];
    public const SHORT = ['ar' => 'ع', 'en' => 'EN', 'nl' => 'NL'];
    public const DEFAULT = 'ar';

    private static string $locale = self::DEFAULT;
    /** @var array<string, array<string,string>> */
    private static array $strings = [];
    private static ?array $overrides = null;

    public static function isLocale(string $l): bool
    {
        return in_array($l, self::LOCALES, true);
    }

    public static function setLocale(string $l): void
    {
        self::$locale = self::isLocale($l) ? $l : self::DEFAULT;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function dir(?string $l = null): string
    {
        return ($l ?? self::$locale) === 'ar' ? 'rtl' : 'ltr';
    }

    /** Default (file) strings for a locale. */
    public static function defaults(string $l): array
    {
        if (!isset(self::$strings[$l])) {
            $file = ASL_ROOT . '/lang/' . $l . '.php';
            self::$strings[$l] = is_file($file) ? require $file : [];
        }
        return self::$strings[$l];
    }

    public static function overrides(): array
    {
        if (self::$overrides === null) {
            self::$overrides = [];
            try {
                foreach (Db::all('SELECT tkey, ar, en, nl FROM translations') as $row) {
                    self::$overrides[$row['tkey']] = $row;
                }
            } catch (\Throwable) {
                // Database not installed yet: fall back to file strings.
            }
        }
        return self::$overrides;
    }

    public static function clearCache(): void
    {
        self::$overrides = null;
    }

    public static function t(string $key, array $replace = [], ?string $locale = null): string
    {
        $l = $locale ?? self::$locale;
        $o = self::overrides();
        $text = (isset($o[$key][$l]) && $o[$key][$l] !== '') ? $o[$key][$l] : (self::defaults($l)[$key] ?? null);
        $text ??= self::defaults('en')[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return $text;
    }

    /** Pick the best locale from the Accept-Language header. */
    public static function negotiate(): string
    {
        $header = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        foreach (explode(',', $header) as $part) {
            $code = substr(trim(explode(';', $part)[0]), 0, 2);
            if (self::isLocale($code)) {
                return $code;
            }
        }
        return self::DEFAULT;
    }

    /** Localised product field, e.g. field($p, 'name') => $p['name_nl'] */
    public static function field(array $row, string $field, ?string $locale = null): string
    {
        $l = $locale ?? self::$locale;
        return (string) ($row[$field . '_' . $l] ?? '');
    }
}
