<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Flowframe\Trend\Trend;
use Filament\Widgets\ChartWidget;
use Dashed\DashedCore\Models\NotFoundPageOccurrence;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class NotFoundPageGlobalStats extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Aantal keer bezocht';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'day';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Dag',
            'month' => 'Maand',
            'year' => 'Jaar',
        ];
    }

    protected function getData(): array
    {
        if ($this->filter == 'day') {
            $method = 'perDay';
            $startDate = now()->subWeek();
        } elseif ($this->filter == 'month') {
            $method = 'perMonth';
            $startDate = now()->subYear();
        } elseif ($this->filter == 'year') {
            $method = 'perYear';
            $startDate = now()->subYears(5);
        }

        $trend = Trend::query(
            NotFoundPageOccurrence::query()
        )
            ->between(start: $startDate, end: now())
            ->{$method}()
            ->count();

        $trend->map(function ($value, $key) use (&$statistics) {
            $statistics['data'][] = $value->aggregate;
            $statistics['labels'][] = $value->date;
        });

        return [
            'datasets' => [
                [
                    'label' => 'Aantal keer bezocht',
                    'data' => $statistics['data'],
                    'backgroundColor' => 'rgba(216, 255, 51, 1)',
                    'borderColor' => 'rgba(216, 255, 51, 1)',
                ],
            ],
            'labels' => $statistics['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
