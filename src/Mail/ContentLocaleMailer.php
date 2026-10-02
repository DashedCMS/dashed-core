<?php

namespace Dashed\DashedCore\Mail;

use Dashed\DashedCore\Classes\AdminLocale;
use Illuminate\Mail\Mailer;

/**
 * Een mail gaat naar een klant of collega, niet naar de beheerder die op de
 * knop drukt. Alles wat hier synchroon wordt opgebouwd doet dat daarom in de
 * inhoudstaal; daarna staat de translator weer op de taal van de beheerder.
 * Mailables, Mail::send() en het mailkanaal van notificaties komen allemaal
 * langs send(). De withLocale() van een mailable met ->locale() wisselt
 * binnen asContent(), en die wissel volgt de translator dan gewoon.
 */
class ContentLocaleMailer extends Mailer
{
    public function send($view, array $data = [], $callback = null)
    {
        return AdminLocale::asContent(fn () => parent::send($view, $data, $callback));
    }
}
