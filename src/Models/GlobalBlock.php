<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public static function booted()
    {
        static::saved(function ($model) {
            Artisan::call('cache:clear');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
