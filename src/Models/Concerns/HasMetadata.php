<?php

namespace Qubiqx\QcommerceCore\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Qubiqx\QcommerceCore\Models\Metadata;

trait HasMetadata
{
    public function metadata(): MorphOne
    {
        return $this->morphOne(Metadata::class, 'metadatable');
    }
}
