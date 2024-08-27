<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Dashed\DashedCore\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
