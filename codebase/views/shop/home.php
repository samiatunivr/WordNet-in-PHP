<?php use Asl\View; ?>
<section class="hero">
  <div class="wrap hero-inner">
    <div class="hero-text">
      <p class="eyebrow"><?= e(t('home.eyebrow')) ?></p>
      <h1><?= e(t('home.hero_title')) ?></h1>
      <p class="lead"><?= nl2br(e(t('home.hero_text'))) ?></p>
      <a class="btn btn-large" href="#shop"><?= e(t('home.hero_cta')) ?></a>
    </div>
    <div class="hero-art" aria-hidden="true">
      <img src="/assets/img/hero.svg" alt="" width="420" height="420">
    </div>
  </div>
</section>

<section class="features">
  <div class="wrap features-grid">
    <?php foreach (['purity', 'origin', 'delivery'] as $f): ?>
      <div class="feature">
        <span class="feature-icon feature-<?= e($f) ?>" aria-hidden="true"></span>
        <h2><?= e(t('home.feature_' . $f . '_title')) ?></h2>
        <p><?= e(t('home.feature_' . $f . '_text')) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section id="shop" class="shop-section">
  <div class="wrap">
    <h2 class="section-title"><?= e(t('home.products_title')) ?></h2>
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

<section id="about" class="about">
  <div class="wrap about-inner">
    <h2 class="section-title"><?= e(t('home.about_title')) ?></h2>
    <p><?= nl2br(e(t('home.about_text'))) ?></p>
  </div>
</section>
