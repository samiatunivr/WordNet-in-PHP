<?php use Asl\I18n; ?>
<h1><?= e(t('admin.texts')) ?></h1>
<p class="hint"><?= e(t('admin.texts_hint')) ?></p>
<form method="post" action="<?= e(admin_url('texts')) ?>" class="admin-form texts-form">
  <?= csrf_field() ?>
  <?php $group = null; foreach ($keys as $key):
    $g = explode('.', $key)[0];
    if ($g !== $group): if ($group !== null): ?></fieldset><?php endif; $group = $g; ?>
      <fieldset><legend><?= e(t('admin.group.' . $g)) ?></legend>
    <?php endif; ?>
    <div class="text-row<?= isset($overrides[$key]) ? ' customised' : '' ?>">
      <code class="text-key" dir="ltr"><?= e($key) ?></code>
      <div class="lang-columns">
        <?php foreach (I18n::LOCALES as $l):
            $val = isset($overrides[$key]) && $overrides[$key][$l] !== '' ? $overrides[$key][$l] : (I18n::defaults($l)[$key] ?? '');
            $long = mb_strlen($val) > 60; ?>
          <label class="lang-col" lang="<?= e($l) ?>" dir="<?= e(I18n::dir($l)) ?>">
            <span class="muted"><?= e(I18n::NAMES[$l]) ?></span>
            <?php if ($long): ?>
              <textarea name="t[<?= e($key) ?>][<?= e($l) ?>]" rows="3" maxlength="5000"><?= e($val) ?></textarea>
            <?php else: ?>
              <input type="text" name="t[<?= e($key) ?>][<?= e($l) ?>]" value="<?= e($val) ?>" maxlength="5000">
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; if ($group !== null): ?></fieldset><?php endif; ?>
  <div class="sticky-save"><button class="btn btn-large" type="submit"><?= e(t('admin.save')) ?></button></div>
</form>
