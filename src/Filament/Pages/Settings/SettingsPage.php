<?php

namespace Qubiqx\QcommerceCore\Filament\Pages\Settings;

use Filament\Pages\Page;

class SettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Instellingen';
    protected static ?string $navigationGroup = 'Overige';
    protected static ?int $navigationSort = 100000;

    protected static string $view = 'qcommerce-core::settings.pages.settings';
}
