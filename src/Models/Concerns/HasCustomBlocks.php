<?php

namespace Qubiqx\QcommerceCore\Models\Concerns;

use Qubiqx\QcommerceCore\Models\CustomBlock;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasCustomBlocks
{
    public function customBlocks(): MorphOne
    {
        return $this->morphOne(CustomBlock::class, 'blockable');
    }
}
