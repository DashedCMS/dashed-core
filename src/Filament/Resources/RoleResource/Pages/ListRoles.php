<?php

namespace Dashed\DashedCore\Filament\Resources\RoleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Models\Role;
use Dashed\DashedCore\Filament\Resources\RoleResource;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createFullAccessRole')
                ->label('Nieuwe rol met alle permissies')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->form([
                    TextInput::make('name')
                        ->label('Naam')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    $allPermissionKeys = array_keys(
                        collect(cms()->getRolePermissions())->collapse()->toArray()
                    );

                    $role = Role::create([
                        'name' => $data['name'],
                        'extra_permissions' => array_values(array_unique($allPermissionKeys)),
                    ]);

                    $this->redirect(RoleResource::getUrl('edit', ['record' => $role]));
                }),

            CreateAction::make(),
        ];
    }
}
