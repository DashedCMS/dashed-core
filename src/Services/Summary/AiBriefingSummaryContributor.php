<?php

namespace Dashed\DashedCore\Services\Summary;

use Throwable;
use Dashed\DashedAi\Facades\Ai;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;
use Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface;

class AiBriefingSummaryContributor implements SummaryContributorInterface
{
    public static function key(): string
    {
        return 'ai_briefing';
    }

    public static function label(): string
    {
        return 'AI-briefing';
    }

    public static function description(): string
    {
        return 'Een door AI geschreven samenvatting met concrete aanbevelingen, op basis van de overige cijfers in deze periode.';
    }

    public static function defaultFrequency(): string
    {
        return 'weekly';
    }

    public static function availableFrequencies(): array
    {
        return ['daily', 'weekly', 'monthly'];
    }

    public static function contribute(SummaryPeriod $period): ?SummarySection
    {
        if (! Ai::hasProvider()) {
            return null;
        }

        $sections = static::collectSourceSections($period);
        if ($sections === []) {
            return null;
        }

        $data = (new SummarySectionSerializer())->toText($sections);
        if (trim($data) === '') {
            return null;
        }

        try {
            $narrative = Ai::text(static::buildPrompt($period, $data));
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! is_string($narrative) || trim($narrative) === '') {
            return null;
        }

        return new SummarySection(
            title: 'AI-briefing',
            blocks: [
                ['type' => 'text', 'data' => ['content' => trim($narrative)]],
            ],
        );
    }

    /** @return array<int, SummarySection> */
    protected static function collectSourceSections(SummaryPeriod $period): array
    {
        $sections = [];

        foreach (SummaryContributorRegistry::map() as $key => $class) {
            if ($key === static::key()) {
                continue;
            }

            try {
                $section = $class::contribute($period);
            } catch (Throwable $e) {
                report($e);
                $section = null;
            }

            if ($section instanceof SummarySection) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    protected static function buildPrompt(SummaryPeriod $period, string $data): string
    {
        return <<<PROMPT
            Je bent een nuchtere e-commerce analist. Schrijf voor de beheerder een korte
            executive briefing (Nederlands) over de periode "{$period->label}".

            Gebruik onderstaande cijfers. Vat het belangrijkste samen in een paar zinnen,
            benoem opvallende trends, en sluit af met 1 tot 3 concrete, haalbare aanbevelingen.
            Verzin geen cijfers die er niet staan. Geen opsomming van alle getallen, maar een verhaal.

            Cijfers:
            {$data}
            PROMPT;
    }
}
