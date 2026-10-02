<?php
use Asl\Cart;
use Asl\Consent;
use Asl\Http;
use Asl\I18n;
use Asl\Security;
use Asl\Settings;

$locale = I18n::locale();
$rest = preg_replace('#^/(ar|en|nl)(?=/|$)#', '', Http::path());
$qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
$flashes = Security::takeFlashes();
$cartCount = session_status() === PHP_SESSION_ACTIVE ? Cart::count() : 0;

// App shell: which bottom tab is active, and where the app-bar back button goes.
$tab = match (true) {
    $rest === '' => 'home',
    $rest === '/shop', str_starts_with($rest, '/product/') => 'shop',
    $rest === '/cart', str_starts_with($rest, '/checkout') => 'cart',
    $rest === '/more', $rest === '/cookies' => 'more',
    default => '',
};
$backUrl = match (true) {
    in_array($rest, ['', '/shop', '/cart', '/more'], true) => null,
    str_starts_with($rest, '/product/') => url('shop'),
    $rest === '/cookies' => url('more'),
    default => url(''),
};
$tabs = [
    'home' => ['', 'nav.home', '<path d="M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>'],
    'shop' => ['shop', 'nav.shop', '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>'],
    'cart' => ['cart', 'nav.cart', '<path d="M3 4h2l2.4 10.2a2 2 0 0 0 2 1.5h7.7a2 2 0 0 0 1.9-1.4L21 8H6.2"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>'],
    'more' => ['more', 'nav.more', '<path d="M4 6h16M4 12h16M4 18h16"/>'],
];
?><!doctype html>
<html lang="<?= e($locale) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($title ?? '') !== '' ? $title . ' · ' . t('site.name') : t('site.name')) ?></title>
<meta name="description" content="<?= e(t('site.tagline')) ?>">
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
<meta name="theme-color" content="#e8a317">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="<?= e(t('site.name')) ?>">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="/assets/css/app.css">
<?php foreach (I18n::LOCALES as $l): ?>
<link rel="alternate" hreflang="<?= e($l) ?>" href="<?= e('/' . $l . $rest) ?>">
<?php endforeach; ?>
<script src="/assets/js/app.js" defer></script>
</head>
<body class="lang-<?= e($locale) ?> tab-<?= e($tab ?: 'none') ?><?= $backUrl ? ' has-back' : '' ?>">
<a class="skip" href="#main"><?= e(t('nav.skip')) ?></a>
<header class="site-header">
  <div class="wrap header-inner">
    <?php if ($backUrl): ?>
      <a class="app-back" href="<?= e($backUrl) ?>" data-back aria-label="<?= e(t('nav.back')) ?>">
        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <span class="app-title"><?= e($title ?? t('site.name')) ?></span>
    <?php endif; ?>
    <a class="brand" href="<?= e(url('')) ?>">
      <img src="/assets/img/logo.svg" alt="" width="40" height="40">
      <span class="brand-text">
        <span class="brand-name"><?= e(t('site.name')) ?></span>
        <span class="brand-sub"><?= e(t('site.tagline_short')) ?></span>
      </span>
    </a>
    <nav class="main-nav" aria-label="<?= e(t('nav.main')) ?>">
      <a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a>
      <a href="<?= e(url('shop')) ?>"><?= e(t('nav.shop')) ?></a>
      <a href="<?= e(url('') . '#about') ?>"><?= e(t('nav.about')) ?></a>
    </nav>
    <div class="header-tools">
      <button type="button" class="btn btn-small btn-outline install-btn" data-install hidden><?= e(t('pwa.install')) ?></button>
      <div class="lang-switch" role="navigation" aria-label="<?= e(t('nav.language')) ?>">
        <?php foreach (I18n::LOCALES as $l): ?>
          <a href="<?= e('/' . $l . $rest . ($qs !== '' ? '?' . $qs : '')) ?>" lang="<?= e($l) ?>" hreflang="<?= e($l) ?>"<?= $l === $locale ? ' aria-current="true" class="active"' : '' ?>><span class="lang-full"><?= e(I18n::NAMES[$l]) ?></span><span class="lang-short" aria-hidden="true"><?= e(I18n::SHORT[$l]) ?></span></a>
        <?php endforeach; ?>
      </div>
      <a class="cart-link" href="<?= e(url('cart')) ?>">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM5.2 4l.4 2H21l-2.2 8.5a2 2 0 0 1-1.9 1.5H8.1a2 2 0 0 1-2-1.6L3.4 2H1V0h3.9l.3 2"/></svg>
        <span class="cart-label"><?= e(t('nav.cart')) ?></span>
        <span class="badge" aria-label="<?= e(t('cart.items_count', ['n' => $cartCount])) ?>"><?= (int) $cartCount ?></span>
      </a>
    </div>
  </div>
</header>

<main id="main">
  <?php if ($flashes): ?>
    <div class="wrap flashes" data-toasts>
      <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>
      <div class="brand-name"><?= e(t('site.name')) ?></div>
      <p><?= e(t('footer.text')) ?></p>
    </div>
    <div>
      <h2 class="footer-title"><?= e(t('footer.contact')) ?></h2>
      <?php $mail = Settings::get('contact_email'); $phone = Settings::get('contact_phone'); ?>
      <?php if ($mail !== ''): ?><p><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></p><?php endif; ?>
      <?php if ($phone !== ''): ?><p dir="ltr"><?= e($phone) ?></p><?php endif; ?>
      <p class="secure-note"><?= e(t('footer.secure_payments')) ?></p>
      <p><a href="<?= e(url('cookies')) ?>"><?= e(t('cookies.settings_link')) ?></a></p>
    </div>
  </div>
  <div class="wrap copyright">© <?= date('Y') ?> <?= e(t('site.name')) ?></div>
</footer>

<nav class="tabbar" aria-label="<?= e(t('nav.app')) ?>">
  <?php foreach ($tabs as $key => [$path, $label, $icon]): ?>
    <a href="<?= e(url($path)) ?>" class="tab<?= $tab === $key ? ' active' : '' ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
      <span class="tab-icon">
        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
        <?php if ($key === 'cart' && $cartCount > 0): ?><span class="tab-badge"><?= (int) $cartCount ?></span><?php endif; ?>
      </span>
      <span class="tab-label"><?= e(t($label)) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<div class="install-help" data-install-help hidden role="dialog" aria-modal="false" aria-labelledby="install-help-title">
  <h2 id="install-help-title"><?= e(t('pwa.install')) ?></h2>
  <p><?= e(t('pwa.ios_help')) ?></p>
  <button type="button" class="btn btn-small" data-install-close><?= e(t('pwa.close')) ?></button>
</div>

<?php if (!Consent::decided() && !str_ends_with(Http::path(), '/cookies')): ?>
  <?= Asl\View::capture('partials/cookie_banner', ['back' => Http::path()]) ?>
<?php endif; ?>
</body>
</html>
