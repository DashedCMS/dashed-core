<?php

namespace Dashed\DashedCore\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Events\MessageSending;
use Dashed\DashedCore\Classes\MailTracking;
use Dashed\DashedCore\Classes\SentEmailScrubber;

/**
 * Zet Postmarks tracking uit en meet kliks en opens op het eigen domein.
 * Adblockers blokkeren track.pstmrk.it met een waarschuwingsscherm.
 *
 * Blijft van een mail af die zelf X-PM-Track-Links zet (die meet zelf) en van
 * een mail met een wachtwoord, code of inloglink. Meten mag verzenden nooit
 * laten falen: bij een fout gaat de mail ongewijzigd de deur uit.
 */
class TrackMailLocally
{
    public function handle(MessageSending $event): void
    {
        if (MailTracking::mode() !== MailTracking::MODE_OWN) {
            return;
        }

        $message = $event->message;
        $headers = $message->getHeaders();
        $measuresItself = $headers->has('X-PM-Track-Links');

        if (! $measuresItself) {
            $headers->addTextHeader('X-PM-Track-Links', 'None');
        }

        if (! $headers->has('X-PM-Track-Opens')) {
            $headers->addTextHeader('X-PM-Track-Opens', 'false');
        }

        if ($measuresItself || SentEmailScrubber::withholdsBody($event->data)) {
            return;
        }

        try {
            $html = $message->getHtmlBody();

            if (! is_string($html) || $html === '') {
                return;
            }

            $id = MailTracking::newId();
            $message->html(MailTracking::apply($html, $id), $message->getHtmlCharset() ?? 'utf-8');
            $headers->addTextHeader(MailTracking::ID_HEADER, $id);
        } catch (\Throwable $e) {
            Log::error('TrackMailLocally kon de mail niet omschrijven: ' . $e->getMessage());
        }
    }
}
