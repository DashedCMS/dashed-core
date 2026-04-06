<?php

namespace Dashed\DashedCore\Filament\Resources\SeoImprovementResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\SeoImprovementResource;

class ListSeoImprovements extends ListRecords
{
    protected static string $resource = SeoImprovementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
