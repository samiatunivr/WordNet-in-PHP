<div class="wrap narrow error-page">
  <p class="error-code"><?= (int) $code ?></p>
  <h1><?= e($title) ?></h1>
  <?php if (!empty($message)): ?><pre class="debug"><?= e($message) ?></pre><?php endif; ?>
  <a class="btn" href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a>
</div>
