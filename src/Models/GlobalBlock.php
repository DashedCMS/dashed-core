<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GlobalBlock extends Model
{
    use HasTranslations;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'dashed__global_blocks';

    protected static $logFillable = true;

    public $translatable = [
        'content',
    ];

    public $casts = [
        'content' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
