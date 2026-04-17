<?php

namespace Dashed\DashedCore\Policies;

use Illuminate\Support\Str;
use Dashed\DashedCore\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for all Filament resources.
 *
 * Bundles Filament's granular policy methods into 4 permissions:
 *   View:{Resource}      - viewAny + view
 *   Edit:{Resource}      - create + update
 *   Delete:{Resource}    - delete + deleteAny + restore + forceDelete + restoreAny + forceDeleteAny
 *   Duplicate:{Resource} - replicate
 */
abstract class BaseResourcePolicy
{
    abstract protected function resourceName(): string;

    protected function perm(string $action): string
    {
        $resource = match ($this->resourceName()) {
            'POS' => 'pos',
            default => Str::snake($this->resourceName()),
        };

        return strtolower($action) . '_' . $resource;
    }

    // ── View bundle ──────────────────────────────────────────────────────────
    public function viewAny(User $user): bool
    {
        return $user->can($this->perm('View'));
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can($this->perm('View'));
    }

    // ── Edit bundle ──────────────────────────────────────────────────────────
    public function create(User $user): bool
    {
        return $user->can($this->perm('Edit'));
    }

    public function update(User $user, Model $record): bool
    {
        return $user->can($this->perm('Edit'));
    }

    // ── Delete bundle ────────────────────────────────────────────────────────
    public function delete(User $user, Model $record): bool
    {
        return $user->can($this->perm('Delete'));
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->perm('Delete'));
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->can($this->perm('Delete'));
    }

    public function restoreAny(User $user): bool
    {
        return $user->can($this->perm('Delete'));
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->can($this->perm('Delete'));
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can($this->perm('Delete'));
    }

    // ── Duplicate ────────────────────────────────────────────────────────────
    public function replicate(User $user, Model $record): bool
    {
        return $user->can($this->perm('Edit'));
    }
}
