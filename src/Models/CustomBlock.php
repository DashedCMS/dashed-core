<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
