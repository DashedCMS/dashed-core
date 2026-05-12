<?php

namespace Dashed\DashedCore\Filament\Support;

use Illuminate\Support\Collection;

/**
 * Build a Filament resource list URL with pre-applied table filters.
 *
 * Usage in a widget stat:
 *
 *     Stat::make('Unhandled orders', $count)
 *         ->url(ResourceFilterUrl::for(OrderResource::class, ['status' => 'unhandled']));
 *
 * Filters are coerced into Filament's `tableFilters[name][value]=…` shape.
 * Pass an array value to forward a multi-select filter as `tableFilters[name][values][]=…`.
 */
class ResourceFilterUrl
{
    public static function for(string $resource, array $filters, string $page = 'index'): string
    {
        $tableFilters = collect($filters)
            ->mapWithKeys(fn ($value, $key) => [
                $key => is_array($value) ? $value : ['value' => $value],
            ])
            ->all();

        return $resource::getUrl($page, ['tableFilters' => $tableFilters]);
    }
}
