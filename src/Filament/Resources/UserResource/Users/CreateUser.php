<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use Dashed\DashedCore\Notifications\AdminNotifier;
use Dashed\DashedCore\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Tijdens create vasthouden zodat afterCreate() de mail kan
     * versturen met het plaintext-wachtwoord.
     */
    protected ?string $plaintextPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plaintextPassword = $data['password'] ?? null;
        $data['password'] = Hash::make($data['password']);

        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->plaintextPassword) {
            return;
        }

        if (! in_array($this->record->role ?? null, ['admin', 'superadmin'], true)) {
            return;
        }

        try {
            AdminNotifier::send(new NewAdminAccountMail($this->record, $this->plaintextPassword), $this->record->email);

            Notification::make()
                ->title('Inloggegevens verstuurd naar ' . $this->record->email)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Account aangemaakt, maar mail kon niet verstuurd worden')
                ->body($e->getMessage())
                ->warning()
                ->send();
        } finally {
            $this->plaintextPassword = null;
        }
    }
}
