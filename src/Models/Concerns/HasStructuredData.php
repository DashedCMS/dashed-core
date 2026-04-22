<?php

namespace Dashed\DashedCore\Models\Concerns;

use Dashed\DashedCore\Models\CustomStructuredData;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStructuredData
{
    public function structuredData(): MorphMany
    {
        return $this->morphMany(CustomStructuredData::class, 'subject')
            ->orderBy('sort_order');
    }
}
