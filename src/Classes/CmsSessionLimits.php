<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;

/**
 * Twee grenzen aan een paneelsessie, naast het automatisch uitloggen bij
 * inactiviteit (CmsIdleTimeout):
 *
 * - Een absolute duur (`cms_session_max_minutes`, standaard 720, 0 = uit).
 *   Wie actief blijft houdt de idle-klok eeuwig op nul, maar na deze duur
 *   moet hij hoe dan ook opnieuw inloggen, inclusief MFA. Het begin van de
 *   sessie staat in de sessie zelf; CmsLogin stempelt het bij een geslaagde
 *   login, en een sessie van voor deze functie krijgt het stempel bij het
 *   eerstvolgende verzoek.
 * - Geen login via het onthoud-mij-cookie voor paneelaccounts. Zo'n login
 *   slaat de MFA-uitdaging van Filament over: het cookie is dan een
 *   wachtwoord dat maanden geldig blijft, en precies dat werd op 31 augustus
 *   2026 misbruikt. Klantaccounts op de front-end blijven erbuiten.
 */
class CmsSessionLimits
{
    public const SETTING_MAX_MINUTES = 'cms_session_max_minutes';

    public const DEFAULT_MAX_MINUTES = 720;

    public const SESSION_KEY_AUTHENTICATED_AT = 'dashed.cms_authenticated_at';

    public static function maxMinutes(): int
    {
        $value = Customsetting::get(self::SETTING_MAX_MINUTES, (string) Sites::getFirstSite()['id'], (string) self::DEFAULT_MAX_MINUTES);

        if ($value === null || $value === '') {
            return self::DEFAULT_MAX_MINUTES;
        }

        return max(0, (int) $value);
    }

    public static function maxIsEnabled(): bool
    {
        return self::maxMinutes() > 0;
    }

    public static function stamp(): void
    {
        session()->put(self::SESSION_KEY_AUTHENTICATED_AT, now()->timestamp);
    }

    public static function authenticatedAt(): ?int
    {
        $value = session(self::SESSION_KEY_AUTHENTICATED_AT);

        return $value ? (int) $value : null;
    }

    public static function maxIsExceeded(): bool
    {
        if (! self::maxIsEnabled()) {
            return false;
        }

        $since = self::authenticatedAt();

        if (! $since) {
            return false;
        }

        return $since + self::maxMinutes() * 60 <= now()->timestamp;
    }
}
