<?php

namespace Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource;

class ListSeoVerbetervoorstellen extends ListRecords
{
    protected static string $resource = SeoVerbetervoorstelResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
