<?php

namespace Dashed\DashedCore\Filament\Resources\AnnouncementBarResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Filament\Resources\AnnouncementBarResource;

class EditAnnouncementBar extends EditRecord
{
    protected static string $resource = AnnouncementBarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
