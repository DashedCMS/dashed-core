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

        // De schakelaar is alleen voor een superadmin zichtbaar en wordt dan
        // ook gevalideerd; dit is het vangnet als iemand de staat zelf invult.
        if (UserResource::superadminChangeError(auth()->user(), null, (bool) ($data['is_superadmin'] ?? false)) !== null) {
            unset($data['is_superadmin']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->plaintextPassword) {
            return;
        }

        // Alleen een nieuwe beheerder krijgt zijn inloggegevens gemaild. De
        // rollen zijn op dit moment al gekoppeld (saveRelationships gaat voor
        // afterCreate).
        if (! $this->record->hasPanelAccess()) {
            return;
        }

        try {
            AdminNotifier::send(new NewAdminAccountMail($this->record, $this->plaintextPassword), $this->record->email);

            Notification::make()
                ->title(__('Inloggegevens verstuurd naar :email', ['email' => $this->record->email]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title(__('Account aangemaakt, maar mail kon niet verstuurd worden'))
                ->body($e->getMessage())
                ->warning()
                ->send();
        } finally {
            $this->plaintextPassword = null;
        }
    }
}
