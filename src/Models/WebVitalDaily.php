<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

class WebVitalDaily extends Model
{
    protected $table = 'dashed__web_vitals_daily';

    protected $fillable = [
        'site_id',
        'date',
        'metric',
        'url_pattern',
        'device',
        'p75',
        'sample_count',
    ];

    protected $casts = [
        'date' => 'date',
        'p75' => 'float',
        'sample_count' => 'integer',
    ];
}
