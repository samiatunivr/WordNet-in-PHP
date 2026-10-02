<?php
use Asl\Cart;
use Asl\Http;
use Asl\I18n;
use Asl\Security;
use Asl\Settings;

$locale = I18n::locale();
$rest = preg_replace('#^/(ar|en|nl)(?=/|$)#', '', Http::path());
$qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
$flashes = Security::takeFlashes();
$cartCount = session_status() === PHP_SESSION_ACTIVE ? Cart::count() : 0;
?><!doctype html>
<html lang="<?= e($locale) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') !== '' ? $title . ' · ' . t('site.name') : t('site.name')) ?></title>
<meta name="description" content="<?= e(t('site.tagline')) ?>">
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<?php foreach (I18n::LOCALES as $l): ?>
<link rel="alternate" hreflang="<?= e($l) ?>" href="<?= e('/' . $l . $rest) ?>">
<?php endforeach; ?>
<script src="/assets/js/app.js" defer></script>
</head>
<body class="lang-<?= e($locale) ?>">
<a class="skip" href="#main"><?= e(t('nav.skip')) ?></a>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url('')) ?>">
      <img src="/assets/img/logo.svg" alt="" width="40" height="40">
      <span class="brand-text">
        <span class="brand-name"><?= e(t('site.name')) ?></span>
        <span class="brand-sub"><?= e(t('site.tagline_short')) ?></span>
      </span>
    </a>
    <nav class="main-nav" aria-label="<?= e(t('nav.main')) ?>">
      <a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a>
      <a href="<?= e(url('') . '#shop') ?>"><?= e(t('nav.shop')) ?></a>
      <a href="<?= e(url('') . '#about') ?>"><?= e(t('nav.about')) ?></a>
    </nav>
    <div class="header-tools">
      <div class="lang-switch" role="navigation" aria-label="<?= e(t('nav.language')) ?>">
        <?php foreach (I18n::LOCALES as $l): ?>
          <a href="<?= e('/' . $l . $rest . ($qs !== '' ? '?' . $qs : '')) ?>" lang="<?= e($l) ?>" hreflang="<?= e($l) ?>"<?= $l === $locale ? ' aria-current="true" class="active"' : '' ?>><?= e(I18n::NAMES[$l]) ?></a>
        <?php endforeach; ?>
      </div>
      <a class="cart-link" href="<?= e(url('cart')) ?>">
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM5.2 4l.4 2H21l-2.2 8.5a2 2 0 0 1-1.9 1.5H8.1a2 2 0 0 1-2-1.6L3.4 2H1V0h3.9l.3 2"/></svg>
        <span><?= e(t('nav.cart')) ?></span>
        <span class="badge" aria-label="<?= e(t('cart.items_count', ['n' => $cartCount])) ?>"><?= (int) $cartCount ?></span>
      </a>
    </div>
  </div>
</header>

<main id="main">
  <?php if ($flashes): ?>
    <div class="wrap flashes">
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
    </div>
  </div>
  <div class="wrap copyright">© <?= date('Y') ?> <?= e(t('site.name')) ?></div>
</footer>
</body>
</html>
