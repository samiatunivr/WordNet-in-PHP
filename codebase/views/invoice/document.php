<?php
/*
 * Invoice document. Inline styles only: the same markup is used in e-mail
 * clients (which ignore stylesheets), the online invoice and the attachment.
 */
use Asl\Config;
use Asl\I18n;
use Asl\Invoices;
use Asl\Units;

$rtl = I18n::dir() === 'rtl';
$start = $rtl ? 'right' : 'left';
$end = $rtl ? 'left' : 'right';
$font = $rtl ? "'Noto Naskh Arabic',Tahoma,Arial,sans-serif" : "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
$ink = '#2f1d07';
$soft = '#6b5235';
$line = '#ecdcbc';
$rate = (int) $order['vat_rate_bp'];
$rateText = rtrim(rtrim(number_format($rate / 100, 2, '.', ''), '0'), '.');
$cell = "padding:10px 8px;border-bottom:1px solid $line;vertical-align:top;";
$sellerName = ($seller['name'] ?? '') !== '' ? $seller['name'] : t('site.name');
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="<?= e(I18n::dir()) ?>" style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid <?= $line ?>;border-radius:14px;font-family:<?= e($font) ?>;color:<?= $ink ?>;font-size:14px;line-height:1.5;">
  <tr>
    <td style="padding:28px 28px 8px;border-top:6px solid #e8a317;border-radius:14px 14px 0 0;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="text-align:<?= $start ?>;vertical-align:top;">
            <img src="<?= e(Config::appUrl() . '/assets/img/icon-192.png') ?>" width="48" height="48" alt="" style="display:inline-block;border:0;vertical-align:middle;">
            <span style="font-size:22px;font-weight:800;vertical-align:middle;padding-<?= $start ?>:8px;"><bdi><?= e($sellerName) ?></bdi></span>
          </td>
          <td style="text-align:<?= $end ?>;vertical-align:top;">
            <div style="font-size:24px;font-weight:800;color:#b86e00;"><?= e(t('invoice.title')) ?></div>
            <div style="color:<?= $soft ?>;font-size:13px;"><?= e(t('invoice.number')) ?>: <strong dir="ltr" style="color:<?= $ink ?>;"><?= e($order['invoice_number']) ?></strong></div>
            <div style="color:<?= $soft ?>;font-size:13px;"><?= e(t('invoice.date')) ?>: <?= e(Invoices::formatDate($order['invoice_date'])) ?></div>
            <div style="color:<?= $soft ?>;font-size:13px;"><?= e(t('invoice.order_ref')) ?>: <span dir="ltr"><?= e(strtoupper(substr($order['public_id'], 0, 10))) ?></span></div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:16px 28px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="50%" style="vertical-align:top;text-align:<?= $start ?>;padding-<?= $end ?>:12px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:<?= $soft ?>;font-weight:700;"><?= e(t('invoice.from')) ?></div>
            <div style="font-weight:700;"><bdi><?= e($sellerName) ?></bdi></div>
            <?php if (($seller['address'] ?? '') !== ''): ?><div dir="auto" style="text-align:<?= $start ?>;"><?= nl2br(e($seller['address'])) ?></div><?php endif; ?>
            <?php if (($seller['vat_number'] ?? '') !== ''): ?><div style="color:<?= $soft ?>;"><?= e(t('invoice.vat_number')) ?>: <span dir="ltr"><?= e($seller['vat_number']) ?></span></div><?php endif; ?>
            <?php if (($seller['coc_number'] ?? '') !== ''): ?><div style="color:<?= $soft ?>;"><?= e(t('invoice.coc_number')) ?>: <span dir="ltr"><?= e($seller['coc_number']) ?></span></div><?php endif; ?>
            <?php if (($seller['email'] ?? '') !== ''): ?><div dir="ltr" style="color:<?= $soft ?>;text-align:<?= $start ?>;"><?= e($seller['email']) ?></div><?php endif; ?>
          </td>
          <td width="50%" style="vertical-align:top;text-align:<?= $start ?>;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:<?= $soft ?>;font-weight:700;"><?= e(t('invoice.bill_to')) ?></div>
            <div style="font-weight:700;"><bdi><?= e($order['customer_name'] ?? '') ?></bdi></div>
            <?php if ($address): ?>
              <div dir="ltr" style="text-align:<?= $start ?>;">
                <?= e($address['line1'] ?? '') ?><br>
                <?php if (!empty($address['line2'])): ?><?= e($address['line2']) ?><br><?php endif; ?>
                <?= e(trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? ''))) ?><br>
                <?= e($address['country'] ?? '') ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($order['customer_email'])): ?><div dir="ltr" style="color:<?= $soft ?>;text-align:<?= $start ?>;"><?= e($order['customer_email']) ?></div><?php endif; ?>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 28px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr style="background:#fff4dc;">
          <th style="<?= $cell ?>text-align:<?= $start ?>;font-size:12px;"><?= e(t('invoice.item')) ?></th>
          <th style="<?= $cell ?>text-align:<?= $end ?>;font-size:12px;"><?= e(t('invoice.qty')) ?></th>
          <th style="<?= $cell ?>text-align:<?= $end ?>;font-size:12px;"><?= e(t('invoice.unit_price')) ?></th>
          <th style="<?= $cell ?>text-align:<?= $end ?>;font-size:12px;"><?= e(t('invoice.amount')) ?></th>
        </tr>
        <?php foreach ($items as $it): ?>
          <tr>
            <td style="<?= $cell ?>text-align:<?= $start ?>;"><bdi><?= e($it['product_name']) ?></bdi></td>
            <td style="<?= $cell ?>text-align:<?= $end ?>;white-space:nowrap;"><?= e($it['quantity']) ?> <?= e(Units::label($it['unit'])) ?></td>
            <td style="<?= $cell ?>text-align:<?= $end ?>;white-space:nowrap;"><?= e(money((int) $it['unit_price_cents'])) ?> / <?= e(Units::label(Units::dimension($it['unit']) === 'mass' ? 'kg' : 'l')) ?></td>
            <td style="<?= $cell ?>text-align:<?= $end ?>;white-space:nowrap;"><?= e(money((int) $it['line_total_cents'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 28px 4px;">
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin-<?= $start ?>:auto;min-width:260px;">
        <tr><td style="padding:4px 8px;text-align:<?= $start ?>;"><?= e(t('cart.subtotal')) ?></td><td style="padding:4px 8px;text-align:<?= $end ?>;white-space:nowrap;"><?= e(money((int) $order['subtotal_cents'])) ?></td></tr>
        <tr><td style="padding:4px 8px;text-align:<?= $start ?>;"><?= e(t('cart.shipping')) ?></td><td style="padding:4px 8px;text-align:<?= $end ?>;white-space:nowrap;"><?= (int) $order['shipping_cents'] === 0 ? e(t('cart.free')) : e(money((int) $order['shipping_cents'])) ?></td></tr>
        <tr><td style="padding:10px 8px 4px;text-align:<?= $start ?>;font-weight:800;font-size:16px;border-top:2px solid <?= $ink ?>;"><?= e(t('cart.total')) ?></td><td style="padding:10px 8px 4px;text-align:<?= $end ?>;font-weight:800;font-size:16px;border-top:2px solid <?= $ink ?>;white-space:nowrap;"><?= e(money((int) $order['total_cents'])) ?></td></tr>
        <?php if ($rate > 0): ?>
          <tr><td style="padding:2px 8px;text-align:<?= $start ?>;color:<?= $soft ?>;font-size:13px;"><?= e(t('invoice.vat_included', ['rate' => $rateText])) ?></td><td style="padding:2px 8px;text-align:<?= $end ?>;color:<?= $soft ?>;font-size:13px;white-space:nowrap;"><?= e(money((int) $order['vat_cents'])) ?></td></tr>
          <tr><td style="padding:2px 8px;text-align:<?= $start ?>;color:<?= $soft ?>;font-size:13px;"><?= e(t('invoice.net_amount')) ?></td><td style="padding:2px 8px;text-align:<?= $end ?>;color:<?= $soft ?>;font-size:13px;white-space:nowrap;"><?= e(money((int) $order['total_cents'] - (int) $order['vat_cents'])) ?></td></tr>
        <?php endif; ?>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:16px 28px 28px;">
      <div style="background:#edf7ee;border:1px solid #b9dfbb;color:#1d4d20;border-radius:10px;padding:10px 14px;">
        <?= e(t('invoice.paid_note', ['date' => Invoices::formatDate($order['paid_at'] ?? $order['invoice_date'])])) ?>
      </div>
      <p style="color:<?= $soft ?>;font-size:13px;margin:16px 0 0;"><?= e(t('invoice.footer')) ?></p>
    </td>
  </tr>
</table>
