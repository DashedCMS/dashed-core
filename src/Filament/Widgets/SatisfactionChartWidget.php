<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Dashed\DashedCore\Satisfaction\Satisfaction;

/**
 * Gemiddelde tevredenheidsscore per week, een lijn per bron (chat, levering),
 * met het aantal antwoorden van de laatste dertig dagen in de omschrijving.
 */
class SatisfactionChartWidget extends ChartWidget
{
    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view_satisfaction');
    }

    public function getHeading(): ?string
    {
        return __('Klanttevredenheid per week');
    }

    public function getDescription(): ?string
    {
        $parts = [];
        foreach (Satisfaction::summary(30) as $source => $row) {
            $parts[] = __(':bron: :gemiddelde (:aantal van :verstuurd beantwoord)', [
                'bron' => Satisfaction::sources()[$source],
                'gemiddelde' => $row['average'] === null ? '-' : number_format($row['average'], 1, ',', ''),
                'aantal' => $row['total'],
                'verstuurd' => $row['sent'],
            ]);
        }

        return __('Laatste 30 dagen').': '.implode(', ', $parts);
    }

    protected function getData(): array
    {
        $data = Satisfaction::weeklyAverages(12);
        $colors = [Satisfaction::SOURCE_CHAT => '#6366f1', Satisfaction::SOURCE_DELIVERY => '#10b981'];

        $datasets = [];
        foreach ($data['series'] as $source => $values) {
            $datasets[] = [
                'label' => Satisfaction::sources()[$source] ?? $source,
                'data' => $values,
                'borderColor' => $colors[$source] ?? '#6b7280',
                'backgroundColor' => $colors[$source] ?? '#6b7280',
                'spanGaps' => true,
            ];
        }

        return ['datasets' => $datasets, 'labels' => $data['labels']];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['min' => 1, 'max' => 5]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
