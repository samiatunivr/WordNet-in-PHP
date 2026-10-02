<?php
use Asl\I18n;
use Asl\Products;
use Asl\Units;

$from = Products::fromPrice($p);
?>
<article class="card">
  <a class="card-media" href="<?= e(url('product/' . rawurlencode($p['slug']))) ?>">
    <img src="<?= e(Products::imageUrl($p['cover'] ?? null)) ?>" alt="<?= e(I18n::field($p, 'name')) ?>" loading="lazy">
    <?php if ((int) $p['is_featured'] === 1): ?><span class="ribbon"><?= e(t('product.featured')) ?></span><?php endif; ?>
  </a>
  <div class="card-body">
    <h3 class="card-title"><a href="<?= e(url('product/' . rawurlencode($p['slug']))) ?>"><?= e(I18n::field($p, 'name')) ?></a></h3>
    <?php if (I18n::field($p, 'summary') !== ''): ?>
      <p class="card-summary"><?= e(I18n::field($p, 'summary')) ?></p>
    <?php endif; ?>
    <div class="card-foot">
      <?php if ($from): ?>
        <span class="price"><?= e(money($from['cents'])) ?> <small>/ <?= e(Units::label($from['per'])) ?></small></span>
      <?php endif; ?>
      <a class="btn btn-small" href="<?= e(url('product/' . rawurlencode($p['slug']))) ?>"><?= e(t('product.choose')) ?></a>
    </div>
  </div>
</article>
