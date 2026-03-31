<?php

namespace Dashed\DashedCore\Traits;

trait HasSettingsPermission
{
    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $permission = cms()->getSettingsPagePermission(static::class);

        if (! $permission) {
            return true;
        }

        return auth()->user()->can($permission);
    }
}
