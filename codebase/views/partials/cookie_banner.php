<section class="cookie-banner" role="region" aria-labelledby="cookie-banner-title">
  <div class="cookie-inner">
    <div class="cookie-text">
      <h2 id="cookie-banner-title"><?= e(t('cookies.banner_title')) ?></h2>
      <p><?= e(t('cookies.banner_text')) ?> <a href="<?= e(url('cookies')) ?>"><?= e(t('cookies.read_more')) ?></a></p>
    </div>
    <form method="post" action="<?= e(url('cookies')) ?>" class="cookie-actions">
      <?= csrf_field() ?>
      <input type="hidden" name="back" value="<?= e($back) ?>">
      <button class="btn btn-outline" type="submit" name="choice" value="necessary"><?= e(t('cookies.only_necessary')) ?></button>
      <a class="btn btn-outline" href="<?= e(url('cookies')) ?>"><?= e(t('cookies.customise')) ?></a>
      <button class="btn" type="submit" name="choice" value="all"><?= e(t('cookies.accept_all')) ?></button>
    </form>
  </div>
</section>
