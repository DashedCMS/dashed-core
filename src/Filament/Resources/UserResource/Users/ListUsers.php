<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\Action;
use Dashed\DashedCore\Models\Role;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
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
                ->label(__('Beheerder aanmaken'))
                ->button()
                // Deze knop deelt toegang uit, dus alleen een superadmin mag hem zien.
                ->visible(fn () => UserResource::canManageAccess(auth()->user()))
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
                    Toggle::make('is_superadmin')
                        ->label(__('Superadmin'))
                        ->helperText(__('Mag altijd alles. Zet dit alleen aan voor wie echt alles moet kunnen.'))
                        ->live(),
                    Select::make('roles')
                        ->label(__('Rollen'))
                        ->multiple()
                        ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Get $get) => ! $get('is_superadmin'))
                        ->required(fn (Get $get) => ! $get('is_superadmin'))
                        ->helperText(__('Kies minstens één rol, of maak deze beheerder superadmin.')),
                ])
                ->action(function (array $data): void {
                    if (! UserResource::canManageAccess(auth()->user())) {
                        abort(403);
                    }

                    $superadmin = (bool) ($data['is_superadmin'] ?? false);
                    $roleIds = $superadmin ? [] : Role::query()->whereKey((array) ($data['roles'] ?? []))->pluck('id')->all();

                    if (! $superadmin && $roleIds === []) {
                        Notification::make()
                            ->title(__('Kies minstens één rol, of maak deze beheerder superadmin.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $password = bin2hex(random_bytes(8));
                    $data['password'] = $password;
                    $user = \Dashed\DashedCore\Models\User::create([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'password' => bcrypt($data['password']),
                        'is_superadmin' => $superadmin,
                    ]);
                    $user->roles()->sync($roleIds);

                    try {
                        AdminNotifier::send(new NewAdminAccountMail($user, $password), $user->email);
                    } catch (\Exception $exception) {
                        Notification::make()
                            ->title(__('Fout bij het verzenden van de e-mail: :bericht', ['bericht' => $exception->getMessage()]))
                            ->danger()
                            ->send();
                    }

                    Notification::make()
                        ->title(__('Beheerder :voornaam :achternaam is aangemaakt.', ['voornaam' => $user->first_name, 'achternaam' => $user->last_name]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
