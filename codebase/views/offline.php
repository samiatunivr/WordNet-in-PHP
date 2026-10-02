<?php use Asl\I18n; ?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#e8a317">
<title><?= e($title . ' · ' . t('site.name')) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="lang-<?= e(I18n::locale()) ?>">
<main class="wrap narrow error-page">
  <img src="/assets/img/logo.svg" alt="" width="72" height="72" class="offline-logo">
  <h1><?= e($title) ?></h1>
  <p class="lead"><?= e(t('pwa.offline_text')) ?></p>
  <a class="btn" href="<?= e(url('')) ?>"><?= e(t('pwa.retry')) ?></a>
</main>
</body>
</html>
