<?php

namespace Dashed\DashedCore\Filament\Resources\ShortLinkResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\ShortLinkResource;

class ListShortLinks extends ListRecords
{
    protected static string $resource = ShortLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
