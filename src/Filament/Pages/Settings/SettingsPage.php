<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;

class SettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Instellingen';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100000;

    protected string $view = 'dashed-core::settings.pages.settings';
}
