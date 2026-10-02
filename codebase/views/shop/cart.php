<?php
use Asl\I18n;
use Asl\Products;
use Asl\Settings;
use Asl\Units;
?>
<div class="wrap cart-page">
  <h1><?= e(t('cart.title')) ?></h1>
  <?php if (!$cart['lines']): ?>
    <div class="empty-box">
      <p><?= e(t('cart.empty')) ?></p>
      <a class="btn" href="<?= e(url('') . '#shop') ?>"><?= e(t('cart.continue')) ?></a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <ul class="cart-lines">
        <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
          <li class="cart-line">
            <img src="<?= e(Products::imageUrl($line['image'])) ?>" alt="" width="88" height="88">
            <div class="cart-line-info">
              <a class="cart-line-name" href="<?= e(url('product/' . rawurlencode($p['slug']))) ?>"><?= e(I18n::field($p, 'name')) ?></a>
              <span class="muted"><?= e(money($line['unit_price'])) ?> / <?= e(Units::label(Units::dimension($line['unit']) === 'mass' ? 'kg' : 'l')) ?></span>
              <form class="inline-form" method="post" action="<?= e(url('cart/update')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="key" value="<?= e($line['key']) ?>">
                <label class="visually-hidden" for="q-<?= e(md5($line['key'])) ?>"><?= e(t('product.quantity')) ?></label>
                <input id="q-<?= e(md5($line['key'])) ?>" class="qty-input" type="text" name="quantity" value="<?= e($line['qty']) ?>" inputmode="decimal" maxlength="12">
                <span class="unit"><?= e(Units::label($line['unit'])) ?></span>
                <button class="btn btn-small btn-outline" type="submit"><?= e(t('cart.update')) ?></button>
              </form>
            </div>
            <div class="cart-line-end">
              <span class="price"><?= e(money($line['line_total'])) ?></span>
              <form method="post" action="<?= e(url('cart/remove')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="key" value="<?= e($line['key']) ?>">
                <button class="link-button" type="submit"><?= e(t('cart.remove')) ?></button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <aside class="summary">
        <h2><?= e(t('cart.summary')) ?></h2>
        <dl>
          <dt><?= e(t('cart.subtotal')) ?></dt><dd><?= e(money($cart['subtotal'])) ?></dd>
          <dt><?= e(t('cart.shipping')) ?></dt><dd><?= $cart['shipping'] === 0 ? e(t('cart.free')) : e(money($cart['shipping'])) ?></dd>
          <dt class="total"><?= e(t('cart.total')) ?></dt><dd class="total"><?= e(money($cart['total'])) ?></dd>
        </dl>
        <?php $free = (int) Settings::get('free_shipping_from_cents'); ?>
        <?php if ($free > 0 && $cart['shipping'] > 0): ?>
          <p class="hint"><?= e(t('cart.free_shipping_hint', ['amount' => money($free - $cart['subtotal'])])) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('checkout')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-large btn-block" type="submit"><?= e(t('cart.checkout')) ?></button>
        </form>
        <p class="secure-note"><?= e(t('cart.secure_note')) ?></p>
      </aside>
    </div>
  <?php endif; ?>
</div>
