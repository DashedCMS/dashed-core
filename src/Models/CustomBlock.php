<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class CustomBlock extends Model
{
    use HasFactory;
    use HasTranslations;
    use LogsActivity;

    protected $table = 'dashed__custom_blocks';

    protected static $logFillable = true;

    public $translatable = [
        'blocks',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
