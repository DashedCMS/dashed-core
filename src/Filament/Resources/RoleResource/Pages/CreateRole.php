<?php

namespace Dashed\DashedCore\Filament\Resources\RoleResource\Pages;

use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Models\Role;
use Dashed\DashedCore\Filament\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Role
    {
        $permissionKeys = [];
        $userIds = $data['users'] ?? [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_')) {
                $permissionKeys = array_merge($permissionKeys, $value ?? []);
            }
        }

        $role = Role::create([
            'name' => $data['name'],
            'extra_permissions' => array_values(array_unique($permissionKeys)),
        ]);

        if ($userIds) {
            User::whereIn('id', $userIds)->each(fn ($user) => $user->assignRole($role));
        }

        return $role;
    }
}
