<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use STS\FilamentImpersonate\Actions\Impersonate;
use Dashed\DashedCore\Notifications\AdminNotifier;
use Dashed\DashedCore\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regeneratePassword')
                ->label(__('Nieuw wachtwoord versturen'))
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('Nieuw wachtwoord aanmaken'))
                ->modalDescription(fn () => __('Hiermee wordt een nieuw willekeurig wachtwoord gegenereerd, opgeslagen voor :email en direct per e-mail verzonden. De gebruiker kan daarna niet meer inloggen met het oude wachtwoord.', ['email' => $this->record->email ?? __('deze gebruiker')]))
                ->modalSubmitActionLabel(__('Nieuw wachtwoord versturen'))
                ->visible(fn () => UserResource::canChangeCredentials(auth()->user(), $this->record))
                ->action(function () {
                    if (! UserResource::canChangeCredentials(auth()->user(), $this->record)) {
                        abort(403);
                    }

                    $password = bin2hex(random_bytes(8));

                    $this->record->forceFill(['password' => Hash::make($password)])->save();

                    try {
                        AdminNotifier::send(new NewAdminAccountMail($this->record, $password), $this->record->email);

                        Notification::make()
                            ->title(__('Nieuw wachtwoord verstuurd'))
                            ->body(__('De inloggegevens zijn naar :email gestuurd.', ['email' => $this->record->email]))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title(__('Wachtwoord is opgeslagen, maar mail kon niet verstuurd worden'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            \Dashed\DashedCore\Filament\Resources\UserResource\Actions\ResetMfaAction::make(),
            Impersonate::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->mayBeManagedBy(auth()->user())),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Het veld is uitgeschakeld of verborgen, maar dat is alleen de
        // weergave: hier wordt het afgedwongen, ook als iemand de staat zelf
        // invult.
        if (! UserResource::canChangeCredentials(auth()->user(), $this->record)) {
            $wijzigtEmail = array_key_exists('email', $data) && (string) $data['email'] !== (string) $this->record->email;

            if ($wijzigtEmail || filled($data['password'] ?? null)) {
                Notification::make()
                    ->title(__('Alleen een superadmin of de gebruiker zelf kan het e-mailadres en wachtwoord van een beheerder wijzigen.'))
                    ->danger()
                    ->send();

                $this->halt();
            }

            unset($data['email']);
        }

        if ($data['password'] ?? null) {
            $this->record->password = Hash::make($data['password']);
            $this->record->save();
        }

        unset($data['password']);
        unset($data['password_confirmation']);

        return $data;
    }
}
