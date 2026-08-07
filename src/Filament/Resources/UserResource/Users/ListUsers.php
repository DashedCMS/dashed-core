<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use Dashed\DashedCore\Notifications\AdminNotifier;
use Dashed\DashedCore\Filament\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('createAdminUser')
                ->label(__('Admin user aanmaken'))
                ->button()
                // Deze knop deelt de superadmin-rol uit, dus alleen een
                // superadmin mag hem zien.
                ->visible(fn () => UserResource::canAssignBackOfficeRole(auth()->user()?->role))
                ->schema([
                    TextInput::make('first_name')
                        ->required()
                        ->label(__('Voornaam')),
                    TextInput::make('last_name')
                        ->required()
                        ->label(__('Achternaam')),
                    TextInput::make('email')
                        ->required()
                        ->unique('users', 'email')
                        ->email()
                        ->label(__('E-mail')),
                ])
                ->action(function (array $data): void {
                    $password = bin2hex(random_bytes(8));
                    $data['password'] = $password;
                    $user = \Dashed\DashedCore\Models\User::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'password' => bcrypt($data['password']),
                        // Superadmin en niet admin: de admin-rol haalt zijn rechten
                        // uit gekoppelde roles en kan zonder die koppeling niets.
                        'role' => 'superadmin',
                    ]);

                    try {
                        AdminNotifier::send(new NewAdminAccountMail($user, $password), $user->email);
                    } catch (\Exception $exception) {
                        Notification::make()
                            ->title(__('Fout bij het verzenden van de e-mail: :bericht', ['bericht' => $exception->getMessage()]))
                            ->danger()
                            ->send();
                    }

                    Notification::make()
                        ->title(__('Admin gebruiker :voornaam :achternaam is aangemaakt.', ['voornaam' => $user->first_name, 'achternaam' => $user->last_name]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
