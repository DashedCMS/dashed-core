<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Actions\Impersonate;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use Dashed\DashedCore\Notifications\AdminNotifier;
use Dashed\DashedCore\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regeneratePassword')
                ->label('Nieuw wachtwoord versturen')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Nieuw wachtwoord aanmaken')
                ->modalDescription(fn () => 'Hiermee wordt een nieuw willekeurig wachtwoord gegenereerd, opgeslagen voor ' . ($this->record->email ?? 'deze gebruiker') . ' en direct per e-mail verzonden. De gebruiker kan daarna niet meer inloggen met het oude wachtwoord.')
                ->modalSubmitActionLabel('Nieuw wachtwoord versturen')
                ->action(function () {
                    $password = bin2hex(random_bytes(8));

                    $this->record->forceFill(['password' => Hash::make($password)])->save();

                    try {
                        AdminNotifier::send(new NewAdminAccountMail($this->record, $password), $this->record->email);

                        Notification::make()
                            ->title('Nieuw wachtwoord verstuurd')
                            ->body('De inloggegevens zijn naar ' . $this->record->email . ' gestuurd.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('Wachtwoord is opgeslagen, maar mail kon niet verstuurd worden')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
