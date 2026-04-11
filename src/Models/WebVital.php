<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

class WebVital extends Model
{
    protected $table = 'dashed__web_vitals';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'metric',
        'value',
        'rating',
        'url',
        'device',
        'created_at',
    ];

    protected $casts = [
        'value' => 'float',
        'created_at' => 'datetime',
    ];
}
