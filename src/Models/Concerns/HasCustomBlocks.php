<?php

namespace Dashed\DashedCore\Models\Concerns;

use Dashed\DashedCore\Models\CustomBlock;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasCustomBlocks
{
    public function customBlocks(): MorphOne
    {
        return $this->morphOne(CustomBlock::class, 'blockable');
    }

    public function getContentBlocksAttribute()
    {
        return $this->customBlocks->blocks ?? [];
    }
}
