<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages;

use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Filament\Resources\Pages\ListRecords;

class ListNotFoundPage extends ListRecords
{
    protected static string $resource = NotFoundPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
          \Dashed\DashedCore\Filament\Widgets\NotFoundPageGlobalStats::class,
        ];
    }
}
