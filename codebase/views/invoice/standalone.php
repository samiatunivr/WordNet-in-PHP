<?php use Asl\I18n; ?><!doctype html>
<html lang="<?= e(I18n::locale()) ?>" dir="<?= e(I18n::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e(t('invoice.title') . ' ' . $order['invoice_number'] . ' · ' . t('site.name')) ?></title>
<style>
  body { margin: 0; padding: 24px 12px; background: #f8f4ec; }
  .print-bar { max-width: 720px; margin: 0 auto 16px; text-align: center; font-family: system-ui, sans-serif; }
  .print-bar button { font: inherit; font-weight: 700; padding: 10px 22px; border-radius: 999px; border: 2px solid #e8a317; background: #e8a317; color: #2b1600; cursor: pointer; }
  @media print { body { background: #fff; padding: 0; } .print-bar { display: none; } }
</style>
</head>
<body>
<?php if (!empty($printable)): ?>
  <div class="print-bar"><button type="button" data-print><?= e(t('invoice.print')) ?></button></div>
  <script src="/assets/js/print.js" defer></script>
<?php endif; ?>
<?= $document ?>
</body>
</html>
