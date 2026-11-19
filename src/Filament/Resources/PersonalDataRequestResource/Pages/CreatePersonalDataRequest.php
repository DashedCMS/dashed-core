<?php

namespace Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Models\PersonalDataRequest;
use Dashed\DashedCore\PersonalData\PersonalDataRunner;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource;

class CreatePersonalDataRequest extends CreateRecord
{
    protected static string $resource = PersonalDataRequestResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ook als iemand de keuzelijst omzeilt: verwijderen vraagt het eigen recht.
        if (($data['type'] ?? null) === PersonalDataRequest::TYPE_ERASE && ! PersonalDataRequestResource::canErase()) {
            abort(403);
        }

        $data['status'] = PersonalDataRequest::STATUS_NEW;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Meteen het droge overzicht: dat is wat de beheerder hierna moet lezen.
        app(PersonalDataRunner::class)->previewRequest($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return PersonalDataRequestResource::getUrl('view', ['record' => $this->record]);
    }
}
