<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'dashed__integration_events';

    protected $guarded = [];

    protected $casts = [
        'ok' => 'boolean',
        'created_at' => 'datetime',
    ];
}
