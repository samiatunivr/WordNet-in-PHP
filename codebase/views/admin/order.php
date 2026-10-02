<?php
use Asl\I18n;
use Asl\Orders;
use Asl\Units;
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <span class="status status-<?= e($order['status']) ?>"><?= e(t('admin.status.' . $order['status'])) ?></span>
</div>
<div class="two-col">
  <section class="admin-section">
    <h2><?= e(t('admin.items')) ?></h2>
    <table class="table">
      <thead><tr><th><?= e(t('admin.name')) ?></th><th><?= e(t('product.quantity')) ?></th><th><?= e(t('admin.unit_price')) ?></th><th><?= e(t('admin.total')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['product_name']) ?></td>
          <td><?= e($it['quantity']) ?> <?= e(Units::label($it['unit'])) ?></td>
          <td class="num"><?= e(money((int) $it['unit_price_cents'])) ?> / <?= e(Units::label(Units::dimension($it['unit']) === 'mass' ? 'kg' : 'l')) ?></td>
          <td class="num"><?= e(money((int) $it['line_total_cents'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="3"><?= e(t('cart.subtotal')) ?></td><td class="num"><?= e(money((int) $order['subtotal_cents'])) ?></td></tr>
      <tr><td colspan="3"><?= e(t('cart.shipping')) ?></td><td class="num"><?= e(money((int) $order['shipping_cents'])) ?></td></tr>
      <tr class="total"><td colspan="3"><?= e(t('cart.total')) ?></td><td class="num"><?= e(money((int) $order['total_cents'])) ?></td></tr>
      </tbody>
    </table>
  </section>
  <section class="admin-section">
    <h2><?= e(t('admin.customer')) ?></h2>
    <dl class="details">
      <dt><?= e(t('admin.name')) ?></dt><dd><?= e($order['customer_name'] ?? '—') ?></dd>
      <dt><?= e(t('admin.email')) ?></dt><dd dir="ltr"><?= e($order['customer_email'] ?? '—') ?></dd>
      <dt><?= e(t('admin.phone')) ?></dt><dd dir="ltr"><?= e($order['customer_phone'] ?? '—') ?></dd>
      <dt><?= e(t('admin.address')) ?></dt>
      <dd dir="ltr"><?php if ($address): ?>
        <?= e($address['line1'] ?? '') ?><br>
        <?php if (!empty($address['line2'])): ?><?= e($address['line2']) ?><br><?php endif; ?>
        <?= e(trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? ''))) ?><br>
        <?php if (!empty($address['state'])): ?><?= e($address['state']) ?><br><?php endif; ?>
        <?= e($address['country'] ?? '') ?>
      <?php else: ?>—<?php endif; ?></dd>
      <dt><?= e(t('admin.language')) ?></dt><dd><?= e(I18n::NAMES[$order['locale']] ?? $order['locale']) ?></dd>
      <dt><?= e(t('admin.date')) ?></dt><dd dir="ltr"><?= e($order['created_at']) ?> UTC</dd>
      <dt><?= e(t('admin.paid_at')) ?></dt><dd dir="ltr"><?= e($order['paid_at'] ?? '—') ?></dd>
      <dt>Stripe</dt><dd dir="ltr" class="break"><?= e($order['stripe_payment_intent'] ?? $order['stripe_session_id'] ?? '—') ?></dd>
      <dt><?= e(t('admin.reference')) ?></dt><dd dir="ltr"><code><?= e(strtoupper(substr($order['public_id'], 0, 10))) ?></code></dd>
    </dl>
    <form method="post" action="<?= e(admin_url('orders/' . $order['id'])) ?>" class="admin-form">
      <?= csrf_field() ?>
      <label><?= e(t('admin.status')) ?>
        <select name="status">
          <?php foreach (Orders::STATUSES as $s): ?>
            <option value="<?= e($s) ?>"<?= $s === $order['status'] ? ' selected' : '' ?>><?= e(t('admin.status.' . $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><?= e(t('admin.note')) ?>
        <textarea name="admin_note" rows="4" maxlength="5000"><?= e($order['admin_note']) ?></textarea>
      </label>
      <p class="hint"><?= e(t('admin.refund_hint')) ?></p>
      <button class="btn" type="submit"><?= e(t('admin.save')) ?></button>
    </form>
  </section>
</div>
