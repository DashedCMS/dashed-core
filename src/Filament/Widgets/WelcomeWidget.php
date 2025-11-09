<?php

namespace Dashed\DashedCore\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WelcomeWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    public ?Model $record = null;

    protected ?string $heading = 'Aantal keer bezocht';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'dashed-core::widgets.welcome-widget';
    protected static ?int $sort = -5;

    public function mount(): void
    {

    }

    protected function getType(): string
    {
        return 'bar';
    }
}
