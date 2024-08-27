<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;

class ViewNotFoundPage extends ViewRecord
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
            \Dashed\DashedCore\Filament\Widgets\NotFoundPageStats::class,
        ];
    }
}
