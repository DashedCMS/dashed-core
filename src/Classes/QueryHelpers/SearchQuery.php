<?php

namespace Dashed\DashedCore\Classes\QueryHelpers;

class SearchQuery
{
    public static function make()
    {
        return function (\Illuminate\Database\Eloquent\Builder $query, string $search): \Illuminate\Database\Eloquent\Builder {
            return $query->search($search);
        };
    }
}
