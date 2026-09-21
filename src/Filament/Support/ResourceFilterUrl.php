<?php

namespace Dashed\DashedCore\Filament\Support;

/**
 * Build a Filament resource list URL with pre-applied table filters.
 *
 * Usage in a widget stat:
 *
 *     Stat::make('Unhandled orders', $count)
 *         ->url(ResourceFilterUrl::for(OrderResource::class, ['status' => 'unhandled']));
 *
 * Filters are coerced into Filament's `filters[name][value]=…` shape.
 * Pass an array value to forward a multi-select filter as `filters[name][values][]=…`.
 *
 * The query-string key is `filters`, not `tableFilters`: Filament 4 binds the
 * `$tableFilters` property to the URL under the alias `filters` and ignores a
 * `tableFilters` parameter, so links built with the property name opened an
 * unfiltered list.
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

        return $resource::getUrl($page, ['filters' => $tableFilters]);
    }
}
