<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
