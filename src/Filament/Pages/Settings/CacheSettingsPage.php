<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;

class CacheSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Cache instellingen';
    protected static ?string $navigationGroup = 'Overige';
    protected static ?string $title = 'Cache instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';
    public array $data = [];

    public function mount(): void
    {
    }

    protected function getFormSchema(): array
    {
        return [
            Placeholder::make('cache')
                ->label('Klik op de knop hieronder om de cache te legen'),
        ];
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
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
