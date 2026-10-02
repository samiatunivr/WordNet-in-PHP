<?php
declare(strict_types=1);

namespace Asl\Controllers;

use Asl\Db;
use Asl\Http;
use Asl\Orders;
use Asl\Stripe;

final class WebhookController
{
    public static function handle(): void
    {
        $payload = (string) file_get_contents('php://input', false, null, 0, 512 * 1024);
        $event = Stripe::verifyWebhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''));
        if ($event === null) {
            Http::json(['error' => 'invalid signature'], 400);
        }

        $eventId = (string) ($event['id'] ?? '');
        if ($eventId === '' || Db::value('SELECT COUNT(*) FROM stripe_events WHERE event_id = ?', [$eventId])) {
            Http::json(['received' => true]);
        }

        $session = $event['data']['object'] ?? [];
        switch ($event['type'] ?? '') {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                Orders::syncFromSession($session);
                break;
            case 'checkout.session.async_payment_failed':
                Orders::syncFromSession($session);
                Orders::setStatusBySession((string) ($session['id'] ?? ''), 'pending', 'failed');
                break;
            case 'checkout.session.expired':
                Orders::setStatusBySession((string) ($session['id'] ?? ''), 'pending', 'cancelled');
                break;
        }

        Db::insert('stripe_events', ['event_id' => $eventId, 'received_at' => Db::now()]);
        Http::json(['received' => true]);
    }
}
