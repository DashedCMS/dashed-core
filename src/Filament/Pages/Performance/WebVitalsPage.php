<?php

namespace Dashed\DashedCore\Filament\Pages\Performance;

use BackedEnum;
use Dashed\DashedCore\Models\WebVitalDaily;
use Filament\Pages\Page;
use UnitEnum;

class WebVitalsPage extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | UnitEnum | null $navigationGroup = 'Performance';

    protected static ?string $navigationLabel = 'Web Vitals';

    protected static ?string $title = 'Core Web Vitals';

    protected string $view = 'dashed-core::filament.pages.performance.web-vitals';

    public int $days = 7;

    public function mount(): void
    {
        //
    }

    public function getRows(): array
    {
        return WebVitalDaily::query()
            ->where('date', '>=', now()->subDays($this->days))
            ->orderBy('metric')
            ->orderByDesc('p75')
            ->get()
            ->groupBy(fn ($r) => $r->metric)
            ->map(fn ($rows) => $rows->sortByDesc('p75')->values())
            ->all();
    }

    public function getThresholds(): array
    {
        return [
            'LCP' => ['good' => 2500, 'poor' => 4000],
            'CLS' => ['good' => 0.1, 'poor' => 0.25],
            'INP' => ['good' => 200, 'poor' => 500],
            'FCP' => ['good' => 1800, 'poor' => 3000],
            'TTFB' => ['good' => 800, 'poor' => 1800],
        ];
    }

    public function ratingFor(string $metric, float $value): string
    {
        $t = $this->getThresholds()[$metric] ?? null;
        if (! $t) {
            return 'unknown';
        }

        return $value <= $t['good'] ? 'good' : ($value <= $t['poor'] ? 'needs-improvement' : 'poor');
    }
}
