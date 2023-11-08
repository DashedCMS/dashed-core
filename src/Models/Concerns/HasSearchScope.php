<?php

namespace Dashed\DashedCore\Models\Concerns;

trait HasSearchScope
{
    public function scopeSearch($query, ?string $search = null)
    {
        if (request()->get('search') ?: $search) {
            $search = strtolower(request()->get('search') ?: $search);
            $loop = 1;
            foreach (self::getTranslatableAttributes() as $attribute) {
                if ($loop == 1) {
                    $query->whereRaw('LOWER(`' . $attribute . '`) LIKE ? ', ['%' . trim(strtolower($search)) . '%']);
                } else {
                    $query->orWhereRaw('LOWER(`' . $attribute . '`) LIKE ? ', ['%' . trim(strtolower($search)) . '%']);
                }
                $loop++;
            }
        }
    }
}
