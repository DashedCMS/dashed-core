<?php

namespace Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages;

use Dashed\DashedCore\Filament\Resources\NotFoundPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Filament\Resources\Pages\ViewRecord;

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
          NotFoundPageResource\Widgets\NotFoundPageStats::class,
        ];
    }
}
