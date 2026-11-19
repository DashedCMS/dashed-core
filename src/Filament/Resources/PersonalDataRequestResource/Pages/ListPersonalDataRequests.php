<?php

namespace Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource;

class ListPersonalDataRequests extends ListRecords
{
    protected static string $resource = PersonalDataRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('Nieuw AVG-verzoek')),
        ];
    }
}
