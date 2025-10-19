<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class HorizonPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Horizon';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100000;

    protected string $view = 'dashed-core::settings.pages.settings';

    public static function getNavigationUrl(): string
    {
        return '/horizon';
    }
}
