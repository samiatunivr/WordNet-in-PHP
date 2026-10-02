<?php
use Asl\I18n;
use Asl\Products;
use Asl\Units;

$name = I18n::field($product, 'name');
$kg = $product['price_per_kg_cents'];
$lt = $product['price_per_l_cents'];
?>
<div class="wrap product-page">
  <nav class="crumbs" aria-label="<?= e(t('nav.breadcrumb')) ?>">
    <a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">›</span>
    <a href="<?= e(url('shop')) ?>"><?= e(t('nav.shop')) ?></a> <span aria-hidden="true">›</span>
    <span aria-current="page"><?= e($name) ?></span>
  </nav>

  <div class="product-layout">
    <div class="carousel" aria-label="<?= e($name) ?>">
      <div class="carousel-track" data-carousel>
        <?php foreach ($images ?: [['filename' => null]] as $i => $img): ?>
          <img src="<?= e(Products::imageUrl($img['filename'])) ?>" alt="<?= e($i === 0 ? $name : '') ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
        <?php endforeach; ?>
      </div>
      <?php if (count($images) > 1): ?>
        <div class="carousel-dots" aria-hidden="true">
          <?php foreach ($images as $i => $img): ?><span<?= $i === 0 ? ' class="active"' : '' ?>></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="gallery" data-gallery>
      <div class="gallery-main">
        <img src="<?= e(Products::imageUrl($images[0]['filename'] ?? null)) ?>" alt="<?= e($name) ?>" data-gallery-main>
      </div>
      <?php if (count($images) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" class="thumb<?= $i === 0 ? ' active' : '' ?>" data-src="<?= e(Products::imageUrl($img['filename'])) ?>" aria-label="<?= e(t('product.image_n', ['n' => $i + 1])) ?>">
              <img src="<?= e(Products::imageUrl($img['filename'])) ?>" alt="" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <h1><?= e($name) ?></h1>
      <?php if (I18n::field($product, 'summary') !== ''): ?>
        <p class="lead"><?= e(I18n::field($product, 'summary')) ?></p>
      <?php endif; ?>

      <ul class="price-list">
        <?php if ($kg !== null): ?><li><span class="price"><?= e(money((int) $kg)) ?></span> / <?= e(Units::label('kg')) ?></li><?php endif; ?>
        <?php if ($lt !== null): ?><li><span class="price"><?= e(money((int) $lt)) ?></span> / <?= e(Units::label('l')) ?></li><?php endif; ?>
      </ul>

      <?php if ($units): ?>
        <form class="buy-box" method="post" action="<?= e(url('cart/add')) ?>"
              data-price-kg="<?= e((string) ($kg ?? '')) ?>" data-price-l="<?= e((string) ($lt ?? '')) ?>"
              data-locale="<?= e(I18n::locale()) ?>" data-currency="<?= e(strtoupper(\Asl\Config::currency())) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <div class="qty-row">
            <label>
              <span><?= e(t('product.quantity')) ?></span>
              <input type="text" name="quantity" value="<?= in_array('g', $units, true) ? '500' : '1' ?>" inputmode="decimal" required maxlength="12" autocomplete="off" data-qty>
            </label>
            <label>
              <span><?= e(t('product.unit')) ?></span>
              <select name="unit" data-unit>
                <?php foreach ($units as $u): ?>
                  <option value="<?= e($u) ?>"<?= $u === (in_array('g', $units, true) ? 'g' : $units[0]) ? ' selected' : '' ?>><?= e(Units::label($u)) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <p class="hint"><?= e(t('product.quantity_hint')) ?></p>
          <div class="buy-actions">
            <p class="estimate" aria-live="polite"><span class="estimate-label"><?= e(t('product.estimate')) ?></span> <strong data-estimate>—</strong></p>
            <button class="btn btn-large" type="submit" name="go" value="stay"><?= e(t('product.add_to_cart')) ?></button>
            <button class="btn btn-outline btn-large" type="submit" name="go" value="cart"><?= e(t('product.buy_now')) ?></button>
          </div>
        </form>
      <?php else: ?>
        <p class="empty"><?= e(t('product.unavailable')) ?></p>
      <?php endif; ?>

      <?php if (I18n::field($product, 'description') !== ''): ?>
        <section class="description">
          <h2><?= e(t('product.description')) ?></h2>
          <p><?= nl2br(e(I18n::field($product, 'description'))) ?></p>
        </section>
      <?php endif; ?>
    </div>
  </div>
</div>
