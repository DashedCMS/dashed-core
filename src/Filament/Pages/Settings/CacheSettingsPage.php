<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;

class CacheSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Cache instellingen';

    protected static ?string $navigationGroup = 'Overige';

    protected static ?string $title = 'Cache instellingen';

    protected static string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    public function mount(): void {}

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
