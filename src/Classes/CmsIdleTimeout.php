<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;

/**
 * Automatisch uitloggen uit het CMS na een tijd zonder activiteit.
 *
 * De sessie van Laravel zelf loopt door zolang de browser open staat (en met
 * een onthoud-mij-cookie ook daarna). Hier staat het tijdstip van de laatste
 * echte actie in de sessie, en wie daarna langer dan de ingestelde termijn
 * niets deed wordt bij zijn volgende verzoek uitgelogd. Instelling op de
 * eerste site (zie [[CmsIpAllowlist]] voor het waarom), standaard 120
 * minuten, 0 zet het uit.
 *
 * Wat als activiteit telt: elke paginalading, en elk Livewire-verzoek dat een
 * methode aanroept of een veld bijwerkt. Een poll (`wire:poll`, Filaments
 * meldingenbel elke 30 seconden) roept alleen `$refresh` aan en telt niet
 * mee, anders zou een open dashboard nooit verlopen. De controle zelf loopt
 * wel op elk verzoek, dus ook een poll stuurt een verlopen sessie naar het
 * inlogscherm.
 */
class CmsIdleTimeout
{
    public const SETTING = 'cms_idle_timeout_minutes';

    public const DEFAULT_MINUTES = 120;

    public const SESSION_KEY = 'dashed.cms_last_activity';

    public static function minutes(): int
    {
        $value = Customsetting::get(self::SETTING, (string) Sites::getFirstSite()['id'], (string) self::DEFAULT_MINUTES);

        if ($value === null || $value === '') {
            return self::DEFAULT_MINUTES;
        }

        return max(0, (int) $value);
    }

    public static function isEnabled(): bool
    {
        return self::minutes() > 0;
    }

    public static function touch(): void
    {
        session()->put(self::SESSION_KEY, now()->timestamp);
    }

    public static function lastActivity(): ?int
    {
        $value = session(self::SESSION_KEY);

        return $value ? (int) $value : null;
    }

    /**
     * Een sessie zonder tijdstip (van voor deze functie, of net ingelogd) is
     * niet verlopen: die krijgt bij dit verzoek zijn eerste stempel.
     */
    public static function isExpired(): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $last = self::lastActivity();

        if (! $last) {
            return false;
        }

        return $last + self::minutes() * 60 <= now()->timestamp;
    }

    /**
     * @param  array<string, mixed>  $payload  De JSON-body van een Livewire-verzoek.
     */
    public static function isActivity(bool $isLivewireRequest, array $payload): bool
    {
        if (! $isLivewireRequest) {
            return true;
        }

        foreach ((array) ($payload['components'] ?? []) as $component) {
            if (! empty($component['updates'])) {
                return true;
            }

            foreach ((array) ($component['calls'] ?? []) as $call) {
                if (($call['method'] ?? null) !== '$refresh') {
                    return true;
                }
            }
        }

        return false;
    }
}
