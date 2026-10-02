<?php
use Asl\I18n;

$rtl = I18n::dir() === 'rtl';
$font = $rtl ? "'Noto Naskh Arabic',Tahoma,Arial,sans-serif" : "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
$align = $rtl ? 'right' : 'left';
$name = trim((string) ($order['customer_name'] ?? ''));
?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('email.invoice_subject', ['number' => $order['invoice_number']])) ?></title>
</head>
<body style="margin:0;padding:0;background:#f8f4ec;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f4ec;">
  <tr><td style="padding:24px 12px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="<?= e(I18n::dir()) ?>" style="max-width:720px;margin:0 auto 16px;font-family:<?= e($font) ?>;color:#2f1d07;font-size:15px;line-height:1.6;text-align:<?= $align ?>;">
      <tr><td>
        <p style="margin:0 0 12px;font-size:17px;font-weight:700;"><?= e($name !== '' ? t('email.greeting', ['name' => $name]) : t('email.greeting_plain')) ?></p>
        <p style="margin:0 0 12px;"><?= e(t('email.invoice_intro')) ?></p>
        <p style="margin:0 0 20px;">
          <a href="<?= e($invoiceUrl) ?>" style="display:inline-block;background:#e8a317;color:#2b1600;font-weight:700;text-decoration:none;padding:10px 22px;border-radius:999px;"><?= e(t('email.view_online')) ?></a>
        </p>
      </td></tr>
    </table>
    <?= $document ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="<?= e(I18n::dir()) ?>" style="max-width:720px;margin:16px auto 0;font-family:<?= e($font) ?>;color:#6b5235;font-size:13px;line-height:1.6;text-align:<?= $align ?>;">
      <tr><td>
        <p style="margin:0 0 6px;"><?= e(t('email.questions')) ?></p>
        <p style="margin:0;"><?= e(t('email.signature')) ?></p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
