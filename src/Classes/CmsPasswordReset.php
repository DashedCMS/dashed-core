<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\CmsPasswordResetRequestedMail;

/**
 * Wachtwoord-reset per mail voor paneelaccounts. Een reset via de mailbox is
 * accountovername zodra die mailbox lek is, en voor een beheerder is dat de
 * mailbox waar ook de MFA-mails heen gaan. Daarom is het per installatie
 * uit te zetten (`cms_admin_password_reset_enabled`, eerste site, standaard
 * aan zodat een uitrol niemand buitensluit): beheerders resetten dan via een
 * collega-superadmin of `dashed:set-password`.
 *
 * Los daarvan geeft elk reset-verzoek op een paneelaccount een
 * beveiligingsmelding aan de ontvangers van SecurityAlerts, zonder de link
 * erin: die mail is een signaal, geen tweede weg naar het wachtwoord. Wat
 * de aanvrager ziet is in beide gevallen hetzelfde, anders verraadt de
 * pagina welke adressen een beheerder zijn.
 *
 * Voor klantaccounts verandert er niets; de front-end had zijn eigen
 * afscherming al (User::mustLoginViaPanel()).
 */
class CmsPasswordReset
{
    public const SETTING_ADMIN_RESET_ENABLED = 'cms_admin_password_reset_enabled';

    public static function adminResetEnabled(): bool
    {
        $value = Customsetting::get(self::SETTING_ADMIN_RESET_ENABLED, (string) Sites::getFirstSite()['id'], '1');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function findPanelUser(?string $email): ?User
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return null;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        return $user && $user->mustLoginViaPanel() ? $user : null;
    }

    /**
     * Melding aan de beveiligingsontvangers, binnen rescue(): een mail die
     * niet weg kan mag de reset zelf niet in de weg zitten.
     */
    public static function notifyRequested(User $user, bool $linkSent): void
    {
        if (! SecurityAlerts::enabled()) {
            return;
        }

        rescue(function () use ($user, $linkSent): void {
            foreach (SecurityAlerts::recipients() as $recipient) {
                Mail::to($recipient)->queue(new CmsPasswordResetRequestedMail(
                    email: (string) $user->email,
                    ip: (string) request()->ip(),
                    userAgent: (string) request()->userAgent(),
                    at: now()->format('d-m-Y H:i'),
                    linkSent: $linkSent,
                ));
            }
        }, report: true);
    }
}
