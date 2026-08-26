<?php

namespace Dashed\DashedCore\Models\Concerns;

use Dashed\DashedCore\Classes\QueryHelpers\TokenizedSearch;

trait HasSearchScope
{
    public function scopeSearch($query, ?string $search = null)
    {
        $search = request()->get('search') ?: $search;

        return TokenizedSearch::apply($query, $search, TokenizedSearch::translatableColumns($this));
    }
}
