<?php

namespace Dashed\DashedCore\Webhooks\Outgoing;

use Dashed\DashedCore\Models\WebhookDelivery;

/**
 * Het tijdstip zit in de handtekening, zodat een ontvanger een opgevangen
 * bericht buiten een venster van vijf minuten kan weigeren.
 */
final class WebhookSigner
{
    public static function signature(string $secret, int $timestamp, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public static function headers(WebhookDelivery $delivery, string $secret, int $timestamp, string $body): array
    {
        return [
            'X-Dashed-Event' => $delivery->event,
            'X-Dashed-Delivery' => (string) $delivery->id,
            'X-Dashed-Timestamp' => (string) $timestamp,
            'X-Dashed-Signature' => self::signature($secret, $timestamp, $body),
            'User-Agent' => 'Dashed-Webhooks/1.0',
        ];
    }
}
