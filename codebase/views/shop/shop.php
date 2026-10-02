<?php use Asl\View; ?>
<section class="shop-section shop-page">
  <div class="wrap">
    <h1 class="section-title"><?= e(t('home.products_title')) ?></h1>
    <p class="section-sub"><?= e(t('home.products_text')) ?></p>
    <?php if (!$products): ?>
      <p class="empty"><?= e(t('home.no_products')) ?></p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($products as $p): ?>
          <?= View::capture('partials/product_card', ['p' => $p]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
