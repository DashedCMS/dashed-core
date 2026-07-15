<?php

namespace Dashed\DashedCore\Listeners;

use Illuminate\Mail\Events\MessageSending;

/**
 * Zet de Postmark open-/link-tracking headers per mail, gestuurd door config.
 * Per site uit te zetten via dashed-core.sent_emails.track_opens_clicks.
 */
class EnablePostmarkTracking
{
    public function handle(MessageSending $event): void
    {
        if (! config('dashed-core.sent_emails.track_opens_clicks', true)) {
            return;
        }

        $headers = $event->message->getHeaders();

        if (! $headers->has('X-PM-Track-Opens')) {
            $headers->addTextHeader('X-PM-Track-Opens', 'true');
        }

        if (! $headers->has('X-PM-Track-Links')) {
            $headers->addTextHeader('X-PM-Track-Links', 'HtmlAndText');
        }
    }
}
