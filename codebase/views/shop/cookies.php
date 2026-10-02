<?php use Asl\Config; ?>
<div class="wrap narrow cookies-page">
  <h1><?= e(t('cookies.title')) ?></h1>
  <p class="lead"><?= nl2br(e(t('cookies.intro'))) ?></p>

  <h2><?= e(t('cookies.used_title')) ?></h2>
  <div class="table-scroll">
  <table class="table">
    <thead><tr><th><?= e(t('cookies.col_name')) ?></th><th><?= e(t('cookies.col_purpose')) ?></th><th><?= e(t('cookies.col_duration')) ?></th><th><?= e(t('cookies.col_type')) ?></th></tr></thead>
    <tbody>
      <tr><td dir="ltr"><code><?= Config::isHttps() ? '__Host-asl' : 'asl' ?></code></td><td><?= e(t('cookies.session_purpose')) ?></td><td><?= e(t('cookies.session_duration')) ?></td><td><?= e(t('cookies.necessary')) ?></td></tr>
      <tr><td dir="ltr"><code>asl_consent</code></td><td><?= e(t('cookies.consent_purpose')) ?></td><td><?= e(t('cookies.consent_duration')) ?></td><td><?= e(t('cookies.necessary')) ?></td></tr>
      <tr><td dir="ltr">Stripe</td><td><?= e(t('cookies.stripe_purpose')) ?></td><td>—</td><td><?= e(t('cookies.necessary')) ?></td></tr>
    </tbody>
  </table>
  </div>

  <h2><?= e(t('cookies.prefs_title')) ?></h2>
  <form method="post" action="<?= e(url('cookies')) ?>" class="cookie-prefs">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e(url('cookies')) ?>">
    <label class="pref">
      <input type="checkbox" checked disabled>
      <span><strong><?= e(t('cookies.necessary')) ?></strong><br><small class="muted"><?= e(t('cookies.necessary_text')) ?></small></span>
    </label>
    <label class="pref">
      <input type="checkbox" name="analytics" value="1"<?= !empty($consent['analytics']) ? ' checked' : '' ?>>
      <span><strong><?= e(t('cookies.analytics')) ?></strong><br><small class="muted"><?= e(t('cookies.analytics_text')) ?></small></span>
    </label>
    <label class="pref">
      <input type="checkbox" name="marketing" value="1"<?= !empty($consent['marketing']) ? ' checked' : '' ?>>
      <span><strong><?= e(t('cookies.marketing')) ?></strong><br><small class="muted"><?= e(t('cookies.marketing_text')) ?></small></span>
    </label>
    <div class="cookie-actions">
      <button class="btn btn-outline" type="submit" name="choice" value="necessary"><?= e(t('cookies.only_necessary')) ?></button>
      <button class="btn btn-outline" type="submit" name="choice" value="custom"><?= e(t('cookies.save_choices')) ?></button>
      <button class="btn" type="submit" name="choice" value="all"><?= e(t('cookies.accept_all')) ?></button>
    </div>
  </form>
  <?php if ($consent !== null): ?>
    <p class="hint"><?= e(t('cookies.current')) ?>:
      <?= e(t('cookies.analytics')) ?> — <?= e(t($consent['analytics'] ? 'cookies.on' : 'cookies.off')) ?>,
      <?= e(t('cookies.marketing')) ?> — <?= e(t($consent['marketing'] ? 'cookies.on' : 'cookies.off')) ?></p>
  <?php endif; ?>
</div>
