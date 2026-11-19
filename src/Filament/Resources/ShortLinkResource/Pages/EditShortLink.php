<?php

namespace Dashed\DashedCore\Filament\Resources\ShortLinkResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Filament\Resources\ShortLinkResource;

class EditShortLink extends EditRecord
{
    protected static string $resource = ShortLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Dashed\DashedCore\Filament\Resources\ShortLinkResource::qrAction(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
