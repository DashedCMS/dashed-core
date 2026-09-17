<?php

namespace Dashed\DashedCore\Mail\Contracts;

/**
 * Markeert een mailable waarvan de inhoud nooit in het logboek van verzonden
 * mails mag komen: een wachtwoord, een eenmalige code, een inloglink. Het
 * logboek bewaart dan alleen de metadata (aan wie, wanneer, welk onderwerp)
 * en een vaste tekst in plaats van de body. Zie SentEmailScrubber.
 */
interface ContainsSecrets
{
}
