<?php

namespace Dashed\DashedCore\Filament\Resources\RoleResource\Pages;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $role = $this->record;

        // Load stored permission keys from JSON column
        $storedPermissions = $role->extra_permissions;
        if (is_string($storedPermissions)) {
            $storedPermissions = json_decode($storedPermissions, true) ?? [];
        }
        if (! is_array($storedPermissions)) {
            $storedPermissions = [];
        }

        // Populate virtual permission fields grouped by group
        foreach (cms()->getRolePermissions() as $group => $permissions) {
            $groupKey = 'permissions_' . md5($group);
            $data[$groupKey] = array_values(array_intersect(array_keys($permissions), $storedPermissions));
        }

        // Populate users (guard_name may be invalid on legacy roles)
        try {
            $data['users'] = $role->users->pluck('id')->toArray();
        } catch (\Throwable) {
            $data['users'] = [];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissionKeys = [];
        $userIds = $data['users'] ?? [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permissions_')) {
                $permissionKeys = array_merge($permissionKeys, $value ?? []);
            }
        }

        $record->update([
            'name' => $data['name'],
            'extra_permissions' => array_values(array_unique($permissionKeys)),
        ]);

        // Sync users: remove role from old users, assign to new ones
        try {
            $currentUserIds = $record->users->pluck('id')->toArray();
        } catch (\Throwable) {
            $currentUserIds = [];
        }
        $toRemove = array_diff($currentUserIds, $userIds);
        $toAdd = array_diff($userIds, $currentUserIds);

        User::whereIn('id', $toRemove)->each(fn ($user) => $user->removeRole($record));
        User::whereIn('id', $toAdd)->each(fn ($user) => $user->assignRole($record));


        return $record;
    }
}
