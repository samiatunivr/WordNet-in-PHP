<?php
declare(strict_types=1);

namespace Asl;

final class Config
{
    private static array $values = [];

    public static function load(string $file): void
    {
        $values = is_file($file) ? require $file : [];
        if (!is_array($values)) {
            throw new \RuntimeException('Configuration file must return an array.');
        }
        self::$values = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $env = getenv(strtoupper($key));
        if ($env !== false && $env !== '') {
            return $env;
        }
        return self::$values[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return self::get('app_env', 'production') !== 'development';
    }

    public static function appUrl(): string
    {
        return rtrim((string) self::get('app_url', ''), '/');
    }

    public static function isHttps(): bool
    {
        return str_starts_with(self::appUrl(), 'https://');
    }

    public static function adminPath(): string
    {
        $path = trim((string) self::get('admin_path', 'admin'), '/');
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $path) ? $path : 'admin';
    }

    public static function currency(): string
    {
        $c = strtolower((string) self::get('currency', 'eur'));
        return preg_match('/^[a-z]{3}$/', $c) ? $c : 'eur';
    }
}
