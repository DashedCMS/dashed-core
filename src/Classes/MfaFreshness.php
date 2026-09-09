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

    public const SESSION_IP_KEY = 'dashed.mfa_verified_ip';

    public const SETTING = 'mfa_reverify_hours';

    public const SETTING_BIND_IP = 'mfa_reverify_on_ip_change';

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

    /**
     * Opnieuw een code vragen zodra het IP-adres van de beheerder afwijkt van
     * het adres waarop de code is bevestigd. Standaard uit: achter een proxy
     * zonder dashed-core.trusted_proxies is elk adres hetzelfde (dan doet het
     * niets), en op mobiele netwerken wisselt het adres geregeld (dan vraagt
     * het vaak).
     */
    public static function bindsIp(): bool
    {
        return filter_var(
            Customsetting::get(self::SETTING_BIND_IP, (string) Sites::getFirstSite()['id'], '0') ?? '0',
            FILTER_VALIDATE_BOOL,
        );
    }

    public static function stamp(): void
    {
        session()->put(self::SESSION_KEY, now()->timestamp);
        session()->put(self::SESSION_IP_KEY, request()->ip());
    }

    public static function verifiedIp(): ?string
    {
        $ip = session(self::SESSION_IP_KEY);

        return $ip ? (string) $ip : null;
    }

    /**
     * Een sessie zonder bewaard adres (van voor deze functie) telt niet als
     * gewisseld: die krijgt het adres bij de volgende stempel.
     */
    public static function ipChanged(): bool
    {
        if (! self::bindsIp()) {
            return false;
        }

        $verifiedIp = self::verifiedIp();

        return $verifiedIp !== null && $verifiedIp !== (string) request()->ip();
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
        if (! self::enabledProviders($user)) {
            return false;
        }

        return (self::hours() > 0 && self::isStale()) || self::ipChanged();
    }
}
