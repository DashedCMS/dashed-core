<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Mail\NewAdminAccountMail;
use Dashed\DashedCore\Filament\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('createAdminUser')
                ->label('Admin user aanmaken')
                ->button()
                ->schema([
                    TextInput::make('first_name')
                        ->required()
                        ->label('Voornaam'),
                    TextInput::make('last_name')
                        ->required()
                        ->label('Achternaam'),
                    TextInput::make('email')
                        ->required()
                        ->unique('users', 'email')
                        ->email()
                        ->label('E-mail'),
                ])
                ->action(function (array $data): void {
                    $password = bin2hex(random_bytes(8));
                    $data['password'] = $password;
                    $user = \Dashed\DashedCore\Models\User::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'password' => bcrypt($data['password']),
                        'role' => 'admin',
                    ]);

                    try {
                        Mail::to($user->email)->send(new NewAdminAccountMail($user, $password));
                    } catch (\Exception $exception) {
                        Notification::make()
                            ->title('Fout bij het verzenden van de e-mail: ' . $exception->getMessage())
                            ->danger()
                            ->send();
                    }

                    Notification::make()
                        ->title('Admin gebruiker ' . $user->first_name . ' ' . $user->last_name . ' is aangemaakt.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
