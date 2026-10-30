<?php

namespace Dashed\DashedCore\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Dashed\DashedCore\Classes\MailTracking;

/**
 * Zet de Postmark open-/link-tracking headers per mail, gestuurd door config.
 * Alleen als dashed-core.sent_emails.tracking op postmark staat.
 */
class EnablePostmarkTracking
{
    public function handle(MessageSending $event): void
    {
        if (MailTracking::mode() !== MailTracking::MODE_POSTMARK) {
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
