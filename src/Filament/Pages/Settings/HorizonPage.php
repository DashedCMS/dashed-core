<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;

class HorizonPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Horizon';

    protected static ?string $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100000;

    protected static string $view = 'dashed-core::settings.pages.settings';

    public static function getNavigationUrl(): string
    {
        return '/horizon';
    }
}
