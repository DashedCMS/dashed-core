<?php

namespace Dashed\DashedCore\Filament\Resources\AnnouncementBarResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\AnnouncementBarResource;

class ListAnnouncementBars extends ListRecords
{
    protected static string $resource = AnnouncementBarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('settings')
                ->label(__('Instellingen'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(\Dashed\DashedCore\Filament\Pages\Settings\AnnouncementBarSettingsPage::getUrl()),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
