<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Widgets;

use Dashed\DashedCore\Models\NotFoundPage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Form;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;

class NotFoundPageStats extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Aantal keer bezocht';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Startdatum'),
                        DatePicker::make('endDate')
                            ->label('Einddatum'),
                        Select::make('time')
                            ->label('Periode')
                            ->options([
                                'perDay' => 'Dag',
                                'perWeek' => 'Week',
                                'perMonth' => 'Maand',
                                'perYear' => 'Jaar',
                            ]),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getData(): array
    {
        $trend = Trend::model(NotFoundPage::class)
            ->between(start: $this->filters['startDate'] ?: now()->subWeek(), end: $this->filters['endDate'] ?: now())
            ->{$this->filters['time'] ?: 'perDay'}()
            ->count();
        dd($trend);

        return [
            'datasets' => [
                [
                    'name' => 'Aantal keer bezocht',
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
