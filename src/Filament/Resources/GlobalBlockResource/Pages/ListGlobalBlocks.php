<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

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
