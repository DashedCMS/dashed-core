<?php

namespace Qubiqx\QcommerceCore\Models\Concerns;

use Qubiqx\QcommerceCore\Models\Metadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMetadata
{
    public function metadata(): MorphOne
    {
        return $this->morphOne(Metadata::class, 'metadatable');
    }
}
