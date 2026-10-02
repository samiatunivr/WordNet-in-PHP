<?php
use Asl\Http;
use Asl\I18n;
use Asl\Settings;

$mail = Settings::get('contact_email');
$phone = Settings::get('contact_phone');
$chev = '<svg class="chev" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<div class="wrap narrow more-page">
  <div class="more-hero">
    <img src="/assets/img/logo.svg" alt="" width="64" height="64">
    <div>
      <h1><?= e(t('site.name')) ?></h1>
      <p class="muted"><?= e(t('site.tagline_short')) ?></p>
    </div>
  </div>

  <h2 class="list-title"><?= e(t('nav.language')) ?></h2>
  <ul class="list">
    <?php foreach (I18n::LOCALES as $l): ?>
      <li><a href="<?= e(url('more', $l)) ?>" lang="<?= e($l) ?>"<?= $l === I18n::locale() ? ' aria-current="true"' : '' ?>>
        <span><?= e(I18n::NAMES[$l]) ?></span>
        <?php if ($l === I18n::locale()): ?><span class="check" aria-hidden="true">✓</span><?php endif; ?>
      </a></li>
    <?php endforeach; ?>
  </ul>

  <ul class="list" data-install-row hidden>
    <li><button type="button" class="list-button" data-install><span><?= e(t('pwa.install')) ?></span><?= $chev ?></button></li>
  </ul>

  <h2 class="list-title"><?= e(t('more.info')) ?></h2>
  <ul class="list">
    <li><a href="<?= e(url('') . '#about') ?>"><span><?= e(t('nav.about')) ?></span><?= $chev ?></a></li>
    <li><a href="<?= e(url('cookies')) ?>"><span><?= e(t('cookies.settings_link')) ?></span><?= $chev ?></a></li>
  </ul>

  <?php if ($mail !== '' || $phone !== ''): ?>
    <h2 class="list-title"><?= e(t('footer.contact')) ?></h2>
    <ul class="list">
      <?php if ($mail !== ''): ?><li><a href="mailto:<?= e($mail) ?>"><span><?= e(t('more.email')) ?></span><span class="muted" dir="ltr"><?= e($mail) ?></span></a></li><?php endif; ?>
      <?php if ($phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><span><?= e(t('more.call')) ?></span><span class="muted" dir="ltr"><?= e($phone) ?></span></a></li><?php endif; ?>
    </ul>
  <?php endif; ?>

  <p class="secure-note center"><?= e(t('footer.secure_payments')) ?></p>
  <p class="muted center small">© <?= date('Y') ?> <?= e(t('site.name')) ?></p>
</div>
