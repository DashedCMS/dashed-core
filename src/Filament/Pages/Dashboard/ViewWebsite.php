<?php

namespace Dashed\DashedCore\Filament\Pages\Dashboard;

use Dashed\DashedPages\Models\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Pages\Dashboard as BaseDashboard;

class ViewWebsite extends Page
{
    protected static ?int $navigationSort = -2;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public array $data = [];

    public function mount(): void
    {
        dd('asdf');
    }
}
