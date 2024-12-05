<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\LocaleSwitcher;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;

class ListGlobalBlocks extends ListRecords
{
    use Translatable;

    protected static string $resource = GlobalBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            LocaleSwitcher::make(),
        ];
    }
}
