<?php

namespace Dashed\DashedCore\Policies;

use Dashed\DashedCore\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'User';
    }

    // Verwijderen van een beheerder of van jezelf valt buiten delete_user;
    // zie User::mayBeManagedBy(). Een superadmin komt via Gate::before
    // langs dit beleid, daarom staat dezelfde toets ook op de acties zelf.

    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record) && $this->mayManage($user, $record);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return parent::forceDelete($user, $record) && $this->mayManage($user, $record);
    }

    protected function mayManage(User $user, Model $record): bool
    {
        return $record instanceof User && $record->mayBeManagedBy($user);
    }
}
