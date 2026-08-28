<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Services\Summary;

use Carbon\Carbon;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;

/**
 * Bouwt het dag-overzicht voor één kalenderdag door ALLE geregistreerde
 * summary-contributors te draaien — dezelfde data als de samenvatting-mail,
 * maar nu opvraagbaar (mobiele app + Filament-pagina).
 *
 * De (dure) AI-briefing wordt per site+dag gecachet voor afgeronde dagen, zodat
 * terugbladeren niet telkens een nieuwe AI-call kost. 'Vandaag' wordt live
 * berekend (de cijfers veranderen nog).
 */
final class DailySummaryBuilder
{
    /**
     * @return array{date:string,label:string,sections:array<int,array{key:string,title:string,blocks:array<int,array<string,mixed>>}>}
     */
    public static function buildForDate(Carbon $date): array
    {
        $day = $date->copy();
        $period = new SummaryPeriod(
            $day->copy()->startOfDay(),
            $day->copy()->endOfDay(),
            'daily',
            ucfirst($day->copy()->locale('nl')->isoFormat('dddd D MMMM YYYY')),
        );

        $sections = [];

        foreach (SummaryContributorRegistry::map() as $key => $class) {
            // De AI-briefing draaien we apart (met caching) en zetten we bovenaan.
            if ($key === AiBriefingSummaryContributor::key()) {
                continue;
            }
            $section = self::safeContribute($class, $period);
            if ($section instanceof SummarySection) {
                $sections[] = ['key' => $key, 'title' => $section->title, 'blocks' => $section->blocks];
            }
        }

        $ai = self::aiSection($period, $day);
        if ($ai !== null) {
            array_unshift($sections, $ai);
        }

        return [
            'date' => $day->toDateString(),
            'label' => $period->label,
            'sections' => $sections,
        ];
    }

    /** @param  class-string<\Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface>  $class */
    private static function safeContribute(string $class, SummaryPeriod $period): ?SummarySection
    {
        try {
            return $class::contribute($period);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return array{key:string,title:string,blocks:array<int,array<string,mixed>>}|null
     */
    private static function aiSection(SummaryPeriod $period, Carbon $day): ?array
    {
        $aiClass = SummaryContributorRegistry::map()[AiBriefingSummaryContributor::key()] ?? null;
        if ($aiClass === null) {
            return null;
        }

        $build = function () use ($aiClass, $period): ?array {
            $section = self::safeContribute($aiClass, $period);

            return $section instanceof SummarySection
                ? ['key' => AiBriefingSummaryContributor::key(), 'title' => $section->title, 'blocks' => $section->blocks]
                : null;
        };

        // Alleen afgeronde dagen cachen; 'vandaag' verandert nog → live.
        if (! $day->copy()->endOfDay()->isPast()) {
            return $build();
        }

        $cacheKey = 'dashed_daily_summary_ai_' . Sites::getActive() . '_' . $day->toDateString();
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === '__none__' ? null : $cached;
        }

        $result = $build();
        Cache::put($cacheKey, $result ?? '__none__', now()->addDays(30));

        return $result;
    }
}
