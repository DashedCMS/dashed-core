<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkEditItem extends Model
{
    protected $table = 'dashed__bulk_edit_items';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'old' => 'array',
        'new' => 'array',
        'skipped' => 'array',
        'undone_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(BulkEditRun::class, 'bulk_edit_run_id');
    }
}
