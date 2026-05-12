<?php

namespace Dashed\DashedCore\Filament\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Support\Str;

/**
 * Filament table column that renders "who last edited this record, when"
 * using the existing spatie/laravel-activitylog data. Pair with
 * `HasLastEditedColumn` on the resource to wire up the eager-load that
 * keeps the column N+1-safe.
 */
class LastEditedColumn extends Column
{
    protected string $view = 'dashed-core::filament.tables.columns.last-edited';

    public function getState(): ?array
    {
        $record = $this->getRecord();
        if (! $record) {
            return null;
        }

        $activity = $record->latestActivity ?? null;
        if ($activity === null && method_exists($record, 'activities')) {
            $activity = $record->activities()->latest('created_at')->first();
        }

        if (! $activity) {
            return null;
        }

        $causer = $activity->causer ?? null;
        $name = $causer && (isset($causer->name) || isset($causer->first_name))
            ? trim(($causer->first_name ?? '') . ' ' . ($causer->last_name ?? '')) ?: ($causer->name ?? '')
            : '';
        if ($name === '') {
            $name = 'Systeem';
        }

        $avatar = null;
        if ($causer && method_exists($causer, 'getFilamentAvatarUrl')) {
            $avatar = $causer->getFilamentAvatarUrl();
        }

        return [
            'causer_name' => $name,
            'causer_avatar_url' => $avatar,
            'causer_initials' => $this->initials($name),
            'created_at' => $activity->created_at,
            'created_at_relative' => $activity->created_at?->diffForHumans(),
        ];
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_filter($parts);
        if (count($parts) === 0) {
            return '??';
        }
        if (count($parts) === 1) {
            return Str::upper(Str::substr($parts[0], 0, 2));
        }
        return Str::upper(Str::substr($parts[0], 0, 1) . Str::substr(end($parts), 0, 1));
    }
}
