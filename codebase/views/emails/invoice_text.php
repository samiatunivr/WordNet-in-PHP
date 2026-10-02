<?php
use Asl\Invoices;
use Asl\Units;

$name = trim((string) ($order['customer_name'] ?? ''));
$lines = [];
$lines[] = $name !== '' ? t('email.greeting', ['name' => $name]) : t('email.greeting_plain');
$lines[] = '';
$lines[] = t('email.invoice_intro');
$lines[] = '';
$lines[] = t('invoice.title') . ' ' . $order['invoice_number'] . ' — ' . Invoices::formatDate($order['invoice_date']);
$lines[] = str_repeat('-', 40);
foreach ($items as $it) {
    $lines[] = $it['product_name'] . ' — ' . $it['quantity'] . ' ' . Units::label($it['unit']) . ': ' . money((int) $it['line_total_cents']);
}
$lines[] = t('cart.shipping') . ': ' . money((int) $order['shipping_cents']);
$lines[] = t('cart.total') . ': ' . money((int) $order['total_cents']);
if ((int) $order['vat_rate_bp'] > 0) {
    $rate = rtrim(rtrim(number_format((int) $order['vat_rate_bp'] / 100, 2, '.', ''), '0'), '.');
    $lines[] = t('invoice.vat_included', ['rate' => $rate]) . ': ' . money((int) $order['vat_cents']);
}
$lines[] = '';
$lines[] = t('email.view_online') . ': ' . $invoiceUrl;
$lines[] = '';
$lines[] = t('email.questions');
$lines[] = t('email.signature');
echo implode("\n", $lines) . "\n";
