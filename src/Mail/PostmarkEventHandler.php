<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Support\Carbon;
use Dashed\DashedCore\Models\SentEmail;

/**
 * Verwerkt Postmark webhook-events en werkt de bijbehorende SentEmail bij.
 * Zelf idempotent: set-once statussen worden alleen gezet als ze leeg zijn,
 * opens/clicks tellen op. Onbekende MessageID wordt genegeerd.
 */
class PostmarkEventHandler
{
    public function handle(array $payload): void
    {
        $messageId = $payload['MessageID'] ?? null;
        $recordType = $payload['RecordType'] ?? null;

        if (! $messageId || ! $recordType) {
            return;
        }

        $mail = SentEmail::where('message_id', $messageId)->first();

        if (! $mail) {
            return;
        }

        match ($recordType) {
            'Delivery' => $this->markDelivered($mail),
            'Bounce' => $this->markBounced($mail, $payload),
            'SpamComplaint' => $this->markComplained($mail),
            'Open' => $this->countOpen($mail),
            'Click' => $this->countClick($mail, $payload),
            default => null,
        };
    }

    protected function markDelivered(SentEmail $mail): void
    {
        $mail->status = SentEmail::STATUS_DELIVERED;
        $mail->delivered_at = $mail->delivered_at ?? Carbon::now();
        $mail->save();
    }

    protected function markBounced(SentEmail $mail, array $payload): void
    {
        $mail->status = SentEmail::STATUS_BOUNCED;
        $mail->bounced_at = $mail->bounced_at ?? Carbon::now();
        $mail->bounce_reason = $payload['Description'] ?? ($payload['Details'] ?? null);
        $mail->save();

        \Dashed\DashedCore\Events\SentEmailBouncedEvent::dispatch($mail, $payload);
    }

    protected function markComplained(SentEmail $mail): void
    {
        $mail->status = SentEmail::STATUS_COMPLAINED;
        $mail->save();

        \Dashed\DashedCore\Events\SentEmailComplainedEvent::dispatch($mail);
    }

    protected function countOpen(SentEmail $mail): void
    {
        $mail->opened_at = $mail->opened_at ?? Carbon::now();
        $mail->open_count = $mail->open_count + 1;
        $mail->save();
    }

    protected function countClick(SentEmail $mail, array $payload): void
    {
        $mail->clicked_at = $mail->clicked_at ?? Carbon::now();
        $mail->click_count = $mail->click_count + 1;
        $mail->last_clicked_url = $payload['OriginalLink'] ?? $mail->last_clicked_url;
        $mail->save();
    }
}
