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
  <section class="admin-section">
    <h2><?= e(t('admin.invoice')) ?></h2>
    <?php if ($order['invoice_number']): ?>
      <dl class="details">
        <dt><?= e(t('invoice.number')) ?></dt><dd dir="ltr"><strong><?= e($order['invoice_number']) ?></strong></dd>
        <dt><?= e(t('invoice.date')) ?></dt><dd dir="ltr"><?= e($order['invoice_date']) ?> UTC</dd>
        <dt><?= e(t('admin.invoice_emailed')) ?></dt><dd dir="ltr"><?= $order['invoice_sent_at'] ? e($order['invoice_sent_at']) . ' UTC' : '<span class="status status-review">' . e(t('admin.invoice_not_sent')) . '</span>' ?></dd>
      </dl>
      <p><a class="btn btn-small btn-outline" href="<?= e(admin_url('orders/' . $order['id'] . '/invoice')) ?>" target="_blank" rel="noopener"><?= e(t('admin.view_invoice')) ?></a></p>
    <?php else: ?>
      <p class="muted"><?= e(t('admin.invoice_none')) ?></p>
    <?php endif; ?>
    <?php if (in_array($order['status'], Asl\Invoices::INVOICEABLE, true)): ?>
      <?php if (Asl\Mailer::validEmail($order['customer_email'])): ?>
        <form method="post" action="<?= e(admin_url('orders/' . $order['id'] . '/invoice')) ?>" data-confirm="<?= e(t('admin.confirm_send_invoice', ['email' => $order['customer_email']])) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-small" type="submit"><?= e($order['invoice_sent_at'] ? t('admin.resend_invoice') : t('admin.send_invoice')) ?></button>
        </form>
      <?php else: ?>
        <p class="hint"><?= e(t('admin.invoice_no_email')) ?></p>
      <?php endif; ?>
    <?php else: ?>
      <p class="hint"><?= e(t('admin.invoice_after_payment')) ?></p>
    <?php endif; ?>
  </section>
</div>
