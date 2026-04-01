<?php

namespace Dashed\DashedCore\Filament\Pages\Dashboard;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static ?int $navigationSort = -2;

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard van ' . Customsetting::get('site_name', null, 'DashedCMS');
    }

    //    protected string $view = 'dashed-ecommerce-core::dashboard.pages.dashboard';

    public array $data = [];

    public static function getStartData(): array
    {
        $dashboardFiltersData = Customsetting::get('dashboard_filter_data_from_user_' . auth()->id(), null, null);
        if ($dashboardFiltersData) {
            $defaultData = self::getDefaultDataByPeriod($dashboardFiltersData['period']);

            return [
                'startDate' => $defaultData['startDate'],
                'endDate' => $defaultData['endDate'],
                'period' => $defaultData['period'],
                'steps' => $defaultData['steps'],
            ];
        }

        return [
            'startDate' => now()->startOfDay()->format('d-m-Y'),
            'endDate' => now()->addDay()->startOfDay()->format('d-m-Y'),
            'period' => 'today',
            'steps' => 'per_hour',
        ];
    }

    public static function getFormatsByStep(string $steps): array
    {
        if ($steps == 'per_hour') {
            $startFormat = 'startOfHour';
            $endFormat = 'endOfHour';
            $addFormat = 'addHour';
        } elseif ($steps == 'per_day') {
            $startFormat = 'startOfDay';
            $endFormat = 'endOfDay';
            $addFormat = 'addDay';
        } elseif ($steps == 'per_week') {
            $startFormat = 'startOfWeek';
            $endFormat = 'endOfWeek';
            $addFormat = 'addWeek';
        } elseif ($steps == 'per_month') {
            $startFormat = 'startOfMonth';
            $endFormat = 'endOfMonth';
            $addFormat = 'addMonth';
        }

        return [
            'startFormat' => $startFormat,
            'endFormat' => $endFormat,
            'addFormat' => $addFormat,
        ];
    }

    public static function getDefaultDataByPeriod(string $period): array
    {
        if ($period == 'today') {
            $startDate = now()->startOfDay();
            $endDate = now()->addDay()->endOfDay();
            $steps = 'per_hour';
        } elseif ($period == 'yesterday') {
            $startDate = now()->subDay()->startOfDay();
            $endDate = now()->endOfDay();
            $steps = 'per_hour';
        } elseif ($period == 'this_week') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
            $steps = 'per_day';
        } elseif ($period == 'week') {
            $startDate = now()->subDays(7)->startOfDay();
            $endDate = now()->endOfDay();
            $steps = 'per_day';
        } elseif ($period == 'this_month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $steps = 'per_day';
        } elseif ($period == 'month') {
            $startDate = now()->subDays(30)->startOfDay();
            $endDate = now()->endOfDay();
            $steps = 'per_day';
        } elseif ($period == 'last_week') {
            $startDate = now()->subWeek()->startOfWeek();
            $endDate = now()->subWeek()->endOfWeek();
            $steps = 'per_day';
        } elseif ($period == 'last_month') {
            $startDate = now()->subMonth()->startOfMonth();
            $endDate = now()->subMonth()->endOfMonth();
            $steps = 'per_day';
        } elseif ($period == 'this_quarter') {
            $startDate = now()->startOfQuarter();
            $endDate = now()->endOfQuarter();
            $steps = 'per_month';
        } elseif ($period == 'last_quarter') {
            $startDate = now()->subQuarter()->startOfQuarter();
            $endDate = now()->subQuarter()->endOfQuarter();
            $steps = 'per_month';
        } elseif ($period == 'this_year') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $steps = 'per_month';
        } elseif ($period == 'last_year') {
            $startDate = now()->subYear()->startOfYear();
            $endDate = now()->subYear()->endOfYear();
            $steps = 'per_month';
        } elseif ($period == 'year') {
            $startDate = now()->subDays(365)->startOfDay();
            $endDate = now()->endOfDay();
            $steps = 'per_month';
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $steps = 'per_day';
        }

        $formats = self::getFormatsByStep($steps);
        $startFormat = $formats['startFormat'];
        $endFormat = $formats['endFormat'];
        $addFormat = $formats['addFormat'];

        return [
            'startDate' => $startDate->format('d-m-Y'),
            'endDate' => $endDate->format('d-m-Y'),
            'period' => $period,
            'steps' => $steps,
            'startFormat' => $startFormat,
            'endFormat' => $endFormat,
            'addFormat' => $addFormat,
        ];
    }

    public static function getPeriodOptions(): array
    {
        return [
            'today' => 'Vandaag',
            'yesterday' => 'Gisteren',
            'this_week' => 'Deze week',
            'last_week' => 'Vorige week',
            'week' => 'Afgelopen 7 dagen',
            'this_month' => 'Deze maand',
            'last_month' => 'Vorige maand',
            'month' => 'Afgelopen 30 dagen',
            'this_quarter' => 'Dit kwartaal',
            'last_quarter' => 'Vorig kwartaal',
            'this_year' => 'Dit jaar',
            'last_year' => 'Vorig jaar',
            'year' => 'Afgelopen 365 dagen',
        ];
    }

    public function mount(): void
    {
        $this->data = self::getStartData();
    }

    public function updateData(): void
    {
        Customsetting::set('dashboard_filter_data_from_user_' . auth()->id(), $this->data);
        $this->dispatch('setPageFiltersData', $this->data);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start datum')
                            ->default(self::getStartData()['startDate'])
                            ->reactive()
                            ->maxDate(fn (callable $get) => $get('endDate') ?: now())
                            ->afterStateUpdated(function () {
                                $this->updateData();
                            }),
                        DatePicker::make('endDate')
                            ->label('Eind datum')
                            ->minDate(fn (callable $get) => $get('startDate'))
                            ->default(self::getStartData()['endDate'])
                            ->reactive()
                            ->afterStateUpdated(function () {
                                $this->updateData();
                            }),
                        Select::make('period')
                            ->label('Periode')
                            ->reactive()
                            ->options(self::getPeriodOptions())
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                $defaultData = self::getDefaultDataByPeriod($state);
                                $set('startDate', $defaultData['startDate']);
                                $set('endDate', $defaultData['endDate']);
                                $set('steps', $defaultData['steps']);
                                $this->updateData();
                            })
                            ->default(self::getStartData()['period']),
                        Select::make('steps')
                            ->label('Stappen')
                            ->reactive()
                            ->options([
                                'per_hour' => 'Per uur',
                                'per_day' => 'Per dag',
                                'per_week' => 'Per week',
                                'per_month' => 'Per maand',
                            ])
                            ->default(self::getStartData()['steps'])
                            ->afterStateUpdated(function () {
                                $this->updateData();
                            }),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ])
            ->statePath('data');
    }
}
