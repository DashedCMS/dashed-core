<?php

namespace Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource;

class ListPageTemplates extends ListRecords
{
    protected static string $resource = PageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
