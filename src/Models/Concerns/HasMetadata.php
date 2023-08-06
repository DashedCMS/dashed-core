<?php

namespace Dashed\DashedCore\Models\Concerns;

use Dashed\DashedCore\Models\Metadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMetadata
{
    public function metadata(): MorphOne
    {
        return $this->morphOne(Metadata::class, 'metadatable');
    }
}
