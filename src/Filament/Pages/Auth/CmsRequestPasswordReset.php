<?php

namespace Dashed\DashedCore\Filament\Pages\Auth;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\LoginAttempt;
use Illuminate\Support\Facades\Password;
use Dashed\DashedCore\Classes\CmsPasswordReset;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

/**
 * Filament bouwt de resetlink met URL::signedRoute(), dus op de Host-header
 * van het verzoek. Hier staat de root tijdens het aanvragen vast op het
 * domein van de site (site_url, anders APP_URL), zodat de link in de mail
 * altijd naar ons eigen domein wijst, wat er ook in de Host-header stond.
 * TrustedHosts houdt vreemde hosts al buiten de deur; dit is de tweede laag
 * voor als die uitstaat.
 *
 * Voor een paneelaccount met de schakelaar uit gaat er geen resetlink en ook
 * geen beveiligingsmelding uit, alleen een regel in het inloglogboek: er is
 * niets gebeurd, en bots die het formulier aflopen leverden anders een mail
 * per poging op. Gaat de link wel de deur uit, dan volgt de melding. Wat de
 * aanvrager ziet is in beide gevallen de gewone "verstuurd"-melding. Zie
 * CmsPasswordReset.
 */
class CmsRequestPasswordReset extends RequestPasswordReset
{
    public function request(): void
    {
        $panelUser = CmsPasswordReset::findPanelUser($this->form->getRawState()['email'] ?? null);

        if ($panelUser && ! CmsPasswordReset::adminResetEnabled()) {
            try {
                $this->rateLimit(2);
            } catch (TooManyRequestsException $exception) {
                $this->getRateLimitedNotification($exception)?->send();

                return;
            }

            LoginAttempt::record(LoginAttempt::RESULT_RESET_REFUSED, $panelUser->email, $panelUser);

            $this->getSentNotification(Password::RESET_LINK_SENT)?->send();
            $this->form->fill();

            return;
        }

        Sites::withForcedRootUrl(fn () => parent::request());

        if ($panelUser) {
            CmsPasswordReset::notifyRequested($panelUser, linkSent: true);
        }
    }
}
