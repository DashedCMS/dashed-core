<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Filament\Actions\CreateAction;
use Filament\Actions\LocaleSwitcher;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedPages\Filament\Resources\PageResource;
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
