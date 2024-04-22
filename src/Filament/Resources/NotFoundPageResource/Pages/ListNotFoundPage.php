<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages;

use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\RedirectResource;

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
          NotFoundPageResource\Widgets\NotFoundPageGlobalStats::class,
        ];
    }
}
