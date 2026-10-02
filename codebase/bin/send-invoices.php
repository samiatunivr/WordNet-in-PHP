<?php
declare(strict_types=1);

/*
 * Safety net for invoice e-mails: sends every invoice that has not been
 * e-mailed yet (e.g. because the mail server was down). Run it from cron:
 *
 *   *\/15 * * * *  php /var/www/asl/codebase/bin/send-invoices.php
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/bootstrap.php';

use Asl\Db;
use Asl\Invoices;
use Asl\Mailer;

$in = implode(',', array_fill(0, count(Invoices::INVOICEABLE), '?'));
$rows = Db::all(
    "SELECT id, customer_email FROM orders WHERE status IN ($in) AND invoice_sent_at IS NULL AND paid_at IS NOT NULL AND paid_at > ? ORDER BY id",
    array_merge(Invoices::INVOICEABLE, [gmdate('Y-m-d H:i:s', time() - 30 * 86400)])
);
$sent = 0;
foreach ($rows as $row) {
    if (!Mailer::validEmail($row['customer_email'])) {
        continue;
    }
    try {
        Invoices::send((int) $row['id']);
        $sent++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Order {$row['id']}: {$e->getMessage()}\n");
    }
}
echo "Invoices sent: $sent\n";
