<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomStructuredData extends Model
{
    protected $table = 'dashed__custom_structured_data';

    protected $fillable = [
        'subject_type', 'subject_id',
        'schema_type', 'json_ld', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
