<?php

namespace Dashed\DashedCore\Filament\Resources\SentEmailResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Dashed\DashedCore\Filament\Resources\SentEmailResource;

class ViewSentEmail extends ViewRecord
{
    protected static string $resource = SentEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
