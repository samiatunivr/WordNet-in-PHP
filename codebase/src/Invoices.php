<?php
declare(strict_types=1);

namespace Asl;

/**
 * Invoices are issued when an order is paid and e-mailed to the customer in
 * the language they shopped in.
 *
 * - Numbers are sequential and gap-free per year (ASL-2026-00001), as Dutch/EU
 *   invoicing rules require.
 * - VAT rate, VAT amount and seller details are frozen on the order when the
 *   invoice is issued, so later setting changes never alter an issued invoice.
 * - Customers open their invoice online through an unguessable token link.
 */
final class Invoices
{
    public const INVOICEABLE = ['paid', 'processing', 'shipped', 'delivered', 'refunded'];

    /** @var int[] */
    private static array $queue = [];

    /** Send after the response has been delivered (keeps Stripe webhooks fast). */
    public static function queue(int $orderId): void
    {
        self::$queue[$orderId] = $orderId;
    }

    public static function flushQueue(): void
    {
        foreach (self::$queue as $id) {
            unset(self::$queue[$id]);
            try {
                self::send($id);
            } catch (\Throwable $e) {
                Orders::log("Invoice e-mail for order $id failed: " . $e->getMessage());
            }
        }
    }

    public static function hasQueue(): bool
    {
        return self::$queue !== [];
    }

    /** Assign number, token, VAT and seller snapshot (idempotent). */
    public static function issue(int $orderId): array
    {
        return Db::transaction(static function () use ($orderId) {
            $order = Db::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
            if (!$order || !in_array($order['status'], self::INVOICEABLE, true)) {
                throw new \RuntimeException('Order is not invoiceable.');
            }
            if ($order['invoice_number'] !== null) {
                return $order;
            }
            $year = gmdate('Y');
            $seq = self::nextNumber('invoice-' . $year);
            $rate = max(0, min(10000, (int) Settings::get('vat_rate_bp')));
            $total = (int) $order['total_cents'];
            // Prices include VAT: VAT = total - total / (1 + rate), rounded half up.
            $net = intdiv($total * 10000 * 2 + (10000 + $rate), (10000 + $rate) * 2);
            $seller = [
                'name' => Settings::get('company_name'),
                'address' => Settings::get('company_address'),
                'vat_number' => Settings::get('vat_number'),
                'coc_number' => Settings::get('coc_number'),
                'email' => Settings::get('contact_email'),
                'phone' => Settings::get('contact_phone'),
            ];
            Db::update('orders', [
                'invoice_number' => sprintf('ASL-%s-%05d', $year, $seq),
                'invoice_token' => Security::randomId(32),
                'invoice_date' => Db::now(),
                'vat_rate_bp' => $rate,
                'vat_cents' => $total - $net,
                'invoice_seller' => json_encode($seller, JSON_UNESCAPED_UNICODE),
                'updated_at' => Db::now(),
            ], 'id = :id', ['id' => $orderId]);
            return Db::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        });
    }

    /** Gap-free counter; the UPDATE row lock serialises concurrent callers. */
    private static function nextNumber(string $name): int
    {
        $stmt = Db::run('UPDATE counters SET value = value + 1 WHERE name = ?', [$name]);
        if ($stmt->rowCount() === 0) {
            Db::insert('counters', ['name' => $name, 'value' => 1]);
            return 1;
        }
        return (int) Db::value('SELECT value FROM counters WHERE name = ?', [$name]);
    }

    /** Issue (if needed) and e-mail the invoice. Returns the updated order. */
    public static function send(int $orderId): array
    {
        $order = self::issue($orderId);
        if (!Mailer::validEmail($order['customer_email'])) {
            throw new \RuntimeException('Order has no valid customer e-mail.');
        }
        $locale = I18n::isLocale($order['locale']) ? $order['locale'] : I18n::DEFAULT;
        $items = Orders::items($orderId);

        [$subject, $html, $text, $doc] = I18n::withLocale($locale, static function () use ($order, $items) {
            $data = self::viewData($order, $items);
            return [
                t('email.invoice_subject', ['number' => $order['invoice_number']]),
                View::capture('emails/invoice', $data + ['document' => View::capture('invoice/document', $data)]),
                View::capture('emails/invoice_text', $data),
                View::capture('invoice/standalone', $data + ['document' => View::capture('invoice/document', $data)]),
            ];
        });

        $bcc = [];
        $shop = Settings::get('contact_email');
        if (Settings::get('invoice_bcc') === '1' && Mailer::validEmail($shop)) {
            $bcc[] = $shop;
        }
        Mailer::send([
            'to' => $order['customer_email'],
            'to_name' => (string) $order['customer_name'],
            'bcc' => $bcc,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'attachments' => [[
                'name' => 'invoice-' . $order['invoice_number'] . '.html',
                'type' => 'text/html; charset=UTF-8',
                'data' => $doc,
            ]],
        ]);
        Db::run('UPDATE orders SET invoice_sent_at = ?, updated_at = ? WHERE id = ?', [Db::now(), Db::now(), $orderId]);
        return Db::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
    }

    public static function url(array $order, ?string $locale = null): string
    {
        $l = $locale ?? (I18n::isLocale($order['locale']) ? $order['locale'] : I18n::DEFAULT);
        return Config::appUrl() . '/' . $l . '/invoice/' . $order['public_id'] . '?t=' . $order['invoice_token'];
    }

    public static function viewData(array $order, array $items): array
    {
        $seller = json_decode((string) $order['invoice_seller'], true);
        $address = json_decode((string) $order['shipping_address'], true);
        return [
            'order' => $order,
            'items' => $items,
            'seller' => is_array($seller) ? $seller : [],
            'address' => is_array($address) ? $address : [],
            'invoiceUrl' => self::url($order, I18n::locale()),
        ];
    }

    public static function formatDate(?string $utc): string
    {
        if (!$utc) {
            return '';
        }
        $ts = strtotime($utc . ' UTC') ?: time();
        if (class_exists(\IntlDateFormatter::class)) {
            $map = ['ar' => 'ar', 'en' => 'en-GB', 'nl' => 'nl-NL'];
            $f = new \IntlDateFormatter($map[I18n::locale()] ?? 'en-GB', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, 'Europe/Amsterdam');
            $out = $f->format($ts);
            if ($out !== false) {
                return $out;
            }
        }
        return gmdate('Y-m-d', $ts);
    }
}
