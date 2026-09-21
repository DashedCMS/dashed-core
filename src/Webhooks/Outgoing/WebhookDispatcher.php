<?php

namespace Dashed\DashedCore\Webhooks\Outgoing;

use Dashed\DashedCore\Jobs\SendWebhookJob;
use Dashed\DashedCore\Models\WebhookDelivery;
use Dashed\DashedCore\Models\WebhookSubscription;

final class WebhookDispatcher
{
    public function dispatch(WebhookSubscription $subscription, string $event, array $payload): ?WebhookDelivery
    {
        if (! $subscription->is_active || ! $subscription->listensTo($event)) {
            return null;
        }

        /** @var WebhookDelivery $delivery */
        $delivery = $subscription->deliveries()->create([
            'event' => $event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        SendWebhookJob::dispatch($delivery->id);

        return $delivery;
    }
}
