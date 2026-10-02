<?php
use Asl\Http;
use Asl\I18n;
use Asl\Security;

$loggedIn = isset($_SESSION['admin_id']);
$flashes = Security::takeFlashes();
$path = Http::path();
$nav = ['' => 'admin.dashboard', 'products' => 'admin.products', 'orders' => 'admin.orders', 'texts' => 'admin.texts', 'settings' => 'admin.settings', 'account' => 'admin.account'];
?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2f1d07">
<meta name="robots" content="noindex, nofollow">
<title><?= e(($title ?? '') . ' · ' . t('site.name') . ' Admin') ?></title>
<link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="/assets/js/app.js" defer></script>
</head>
<body class="admin lang-<?= e(I18n::locale()) ?>">
<header class="admin-header">
  <a class="brand" href="<?= e(admin_url()) ?>">
    <img src="/assets/img/logo.svg" alt="" width="32" height="32">
    <span class="brand-name"><?= e(t('site.name')) ?></span> <span class="tag">Admin</span>
  </a>
  <?php if ($loggedIn): ?>
    <nav class="admin-nav">
      <?php foreach ($nav as $slug => $key): $href = admin_url($slug); ?>
        <a href="<?= e($href) ?>"<?= ($slug === '' ? $path === $href : str_starts_with($path, $href)) ? ' class="active"' : '' ?>><?= e(t($key)) ?></a>
      <?php endforeach; ?>
      <a href="/" target="_blank" rel="noopener"><?= e(t('admin.view_shop')) ?></a>
    </nav>
  <?php endif; ?>
  <div class="admin-tools">
    <form method="post" action="<?= e(admin_url('language')) ?>" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="back" value="<?= e($path) ?>">
      <?php foreach (I18n::LOCALES as $l): ?>
        <button type="submit" name="locale" value="<?= e($l) ?>" class="link-button<?= $l === I18n::locale() ? ' active' : '' ?>" lang="<?= e($l) ?>"><?= e(I18n::NAMES[$l]) ?></button>
      <?php endforeach; ?>
    </form>
    <?php if ($loggedIn): ?>
      <form method="post" action="<?= e(admin_url('logout')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-small btn-outline" type="submit"><?= e(t('admin.logout')) ?></button>
      </form>
    <?php endif; ?>
  </div>
</header>
<main class="admin-main">
  <?php foreach ($flashes as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
</body>
</html>
