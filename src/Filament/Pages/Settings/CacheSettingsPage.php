<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;

class CacheSettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Cache instellingen';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?string $title = 'Cache instellingen';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void
    {
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('Klik op de knop hieronder om de cache te legen'),
        ]);
    }

    public function submit()
    {
        Cache::clear();

        Notification::make()
            ->title('De cache is geleegd')
            ->success()
            ->send();
    }
}
