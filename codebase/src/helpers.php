<?php
declare(strict_types=1);

/** HTML-escape any value for output in element content or quoted attributes. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Translate a UI key in the current locale. */
function t(string $key, array $replace = []): string
{
    return Asl\I18n::t($key, $replace);
}

/** Localised URL inside the shop, e.g. url('cart') => /nl/cart */
function url(string $path = '', ?string $locale = null): string
{
    $locale ??= Asl\I18n::locale();
    return '/' . $locale . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function admin_url(string $path = ''): string
{
    return '/' . Asl\Config::adminPath() . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function money(int $cents): string
{
    return Asl\Money::format($cents);
}

function csrf_field(): string
{
    return Asl\Security::csrfField();
}
