<?php

namespace Dashed\DashedCore\Filament\Resources\ReportResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Filament\Resources\ReportResource;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ReportResource::getUrl('view', ['record' => $this->record]);
    }
}
