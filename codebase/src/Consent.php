<?php
declare(strict_types=1);

namespace Asl;

/**
 * Cookie consent (GDPR / ePrivacy).
 *
 * Strictly necessary cookies (session for cart, checkout and security; the
 * consent cookie itself) are always set. Optional categories are only
 * allowed after an explicit opt-in. The shop currently sets no optional
 * cookies; gate any future analytics/marketing code with Consent::allows().
 */
final class Consent
{
    public const COOKIE = 'asl_consent';
    /** Bump when the cookie policy changes so visitors are asked again. */
    public const VERSION = 1;
    public const OPTIONAL = ['analytics', 'marketing'];
    private const LIFETIME = 180 * 86400; // ask again after ~6 months

    /** @return array<string,bool>|null null when the visitor has not decided yet */
    public static function current(): ?array
    {
        $raw = $_COOKIE[self::COOKIE] ?? '';
        if (!is_string($raw) || !preg_match('/^v(\d+)\.([01])([01])$/', $raw, $m) || (int) $m[1] !== self::VERSION) {
            return null;
        }
        return ['analytics' => $m[2] === '1', 'marketing' => $m[3] === '1'];
    }

    public static function decided(): bool
    {
        return self::current() !== null;
    }

    public static function allows(string $category): bool
    {
        return (self::current()[$category] ?? false) === true;
    }

    /** @param array<string,bool> $choices */
    public static function store(array $choices): void
    {
        $value = 'v' . self::VERSION . '.' . (!empty($choices['analytics']) ? '1' : '0') . (!empty($choices['marketing']) ? '1' : '0');
        setcookie(self::COOKIE, $value, [
            'expires' => time() + self::LIFETIME,
            'path' => '/',
            'secure' => Config::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $value;
    }
}
