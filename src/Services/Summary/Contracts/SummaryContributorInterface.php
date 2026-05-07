<?php

namespace Dashed\DashedCore\Services\Summary\Contracts;

use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;

/**
 * Contract voor packages die een sectie willen bijdragen aan de
 * admin samenvatting-mail. Elke contributor is een class met
 * uitsluitend statische methoden, zodat de registry alleen
 * class-namen hoeft op te slaan en pas bij dispatch instantieert.
 */
interface SummaryContributorInterface
{
    /**
     * Stabiele key, gebruikt in de DB en in Customsetting (snake_case).
     * Mag nooit veranderen na release, zit in de unique-index van
     * dashed__summary_subscriptions.
     */
    public static function key(): string;

    /**
     * Nederlands label voor de UI, bv. "Omzet" of "Popup statistieken".
     */
    public static function label(): string;

    /**
     * Korte uitleg, getoond als helperText in de Filament-form.
     */
    public static function description(): string;

    /**
     * Een van: 'off', 'daily', 'weekly', 'monthly'. Wordt gebruikt
     * als nieuwe gebruikers nog geen voorkeur hebben en de site
     * geen Customsetting-default heeft.
     */
    public static function defaultFrequency(): string;

    /**
     * Subset van ['daily', 'weekly', 'monthly']. Een puur maandelijks
     * rapport laat 'daily' weg zodat de UI het niet aanbiedt.
     *
     * @return array<int, string>
     */
    public static function availableFrequencies(): array;

    /**
     * Bouwt de section voor de opgegeven periode. Mag null retourneren
     * als er voor deze periode geen relevante data is, dan wordt de
     * sectie overgeslagen in de mail.
     */
    public static function contribute(SummaryPeriod $period): ?SummarySection;
}
