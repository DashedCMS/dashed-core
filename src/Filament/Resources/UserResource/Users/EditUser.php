<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Actions\Impersonate;
use Dashed\DashedCore\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['password']) {
            $this->record->password = Hash::make($data['password']);
            $this->record->save();
        }

        unset($data['password']);
        unset($data['password_confirmation']);

        return $data;
    }
}
