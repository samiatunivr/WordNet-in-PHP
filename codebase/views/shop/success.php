<?php use Asl\Units; ?>
<div class="wrap narrow success-page">
  <?php if (in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered'], true)): ?>
    <div class="success-icon" aria-hidden="true">✓</div>
    <h1><?= e(t('success.title')) ?></h1>
    <p class="lead"><?= e(t('success.text')) ?></p>
  <?php else: ?>
    <h1><?= e(t('success.pending_title')) ?></h1>
    <p class="lead"><?= e(t('success.pending_text')) ?></p>
  <?php endif; ?>

  <p><?= e(t('success.reference')) ?>: <code dir="ltr"><?= e(strtoupper(substr($order['public_id'], 0, 10))) ?></code></p>
  <?php if (!empty($order['customer_email'])): ?>
    <p><?= e(t('success.email_note', ['email' => $order['customer_email']])) ?></p>
  <?php endif; ?>

  <table class="table">
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['product_name']) ?></td>
          <td><?= e($it['quantity']) ?> <?= e(Units::label($it['unit'])) ?></td>
          <td class="num"><?= e(money((int) $it['line_total_cents'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="2"><?= e(t('cart.shipping')) ?></td><td class="num"><?= e(money((int) $order['shipping_cents'])) ?></td></tr>
      <tr class="total"><td colspan="2"><?= e(t('cart.total')) ?></td><td class="num"><?= e(money((int) $order['total_cents'])) ?></td></tr>
    </tbody>
  </table>
  <?php if (!empty($order['invoice_number'])): ?>
    <p><a class="btn btn-outline" href="<?= e(Asl\Invoices::url($order, Asl\I18n::locale())) ?>" target="_blank" rel="noopener"><?= e(t('success.view_invoice')) ?></a></p>
  <?php elseif (in_array($order['status'], ['paid', 'processing'], true)): ?>
    <p class="hint"><?= e(t('success.invoice_coming')) ?></p>
  <?php endif; ?>
  <a class="btn" href="<?= e(url('')) ?>"><?= e(t('cart.continue')) ?></a>
</div>
