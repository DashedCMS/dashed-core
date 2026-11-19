<?php

namespace Dashed\DashedCore\Policies;

use Dashed\DashedCore\Models\User;
use Illuminate\Database\Eloquent\Model;

class PersonalDataRequestPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'PersonalDataRequest';
    }

    /** Een verzoek is bewijs en wordt nooit verwijderd of bewerkt. */
    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Model $record): bool
    {
        return false;
    }

    /** Het eigen recht voor de onomkeerbare stap. */
    public function erase(User $user): bool
    {
        return $user->can('erase_personal_data');
    }
}
