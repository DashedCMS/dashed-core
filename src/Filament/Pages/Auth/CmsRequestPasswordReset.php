<?php

namespace Dashed\DashedCore\Filament\Pages\Auth;

use Dashed\DashedCore\Classes\Sites;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;

/**
 * Filament bouwt de resetlink met URL::signedRoute(), dus op de Host-header
 * van het verzoek. Hier staat de root tijdens het aanvragen vast op het
 * domein van de site (site_url, anders APP_URL), zodat de link in de mail
 * altijd naar ons eigen domein wijst, wat er ook in de Host-header stond.
 * TrustedHosts houdt vreemde hosts al buiten de deur; dit is de tweede laag
 * voor als die uitstaat.
 */
class CmsRequestPasswordReset extends RequestPasswordReset
{
    public function request(): void
    {
        Sites::withForcedRootUrl(fn () => parent::request());
    }
}
