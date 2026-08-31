<?php

namespace Dashed\DashedCore\Classes;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;

/**
 * Hoe vers de MFA-bevestiging van de huidige sessie is.
 *
 * Filament controleert de code één keer, bij het inloggen; daarna is een
 * sessie onbeperkt geldig en logt een onthoud-mij-cookie iemand later opnieuw
 * in zonder code. Hier staat het tijdstip van de laatste geslaagde code in de
 * sessie, en de vraag of dat nog binnen de ingestelde termijn valt. Een sessie
 * zonder tijdstip (onthoud-mij, of MFA pas na het inloggen ingesteld) is per
 * definitie niet vers.
 *
 * De termijn is één instelling voor de hele installatie en staat daarom op de
 * eerste site, om dezelfde reden als [[CmsIpAllowlist]]: get() zonder site
 * valt terug op de actieve site, set() op de eerste.
 */
class MfaFreshness
{
    public const SESSION_KEY = 'dashed.mfa_verified_at';

    public const SETTING = 'mfa_reverify_hours';

    public const DEFAULT_HOURS = 24;

    public static function hours(): int
    {
        $value = Customsetting::get(self::SETTING, (string) Sites::getFirstSite()['id'], (string) self::DEFAULT_HOURS);

        return max(0, (int) $value);
    }

    /**
     * @return array<string, MultiFactorAuthenticationProvider>
     */
    public static function enabledProviders(?Authenticatable $user): array
    {
        if (! $user) {
            return [];
        }

        return array_filter(
            Filament::getMultiFactorAuthenticationProviders(),
            fn (MultiFactorAuthenticationProvider $provider): bool => $provider->isEnabled($user),
        );
    }

    public static function stamp(): void
    {
        session()->put(self::SESSION_KEY, now()->timestamp);
    }

    public static function stampIfUserHasMfa(?Authenticatable $user): void
    {
        if (self::enabledProviders($user)) {
            self::stamp();
        }
    }

    public static function verifiedAt(): ?Carbon
    {
        $timestamp = session(self::SESSION_KEY);

        return $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }

    public static function isStale(): bool
    {
        $hours = self::hours();

        if ($hours <= 0) {
            return false;
        }

        $verifiedAt = self::verifiedAt();

        return ! $verifiedAt || $verifiedAt->copy()->addHours($hours)->isPast();
    }

    public static function needsReverification(?Authenticatable $user): bool
    {
        return self::hours() > 0
            && self::enabledProviders($user)
            && self::isStale();
    }
}
