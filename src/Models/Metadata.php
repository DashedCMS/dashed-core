<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Metadata extends Model
{
    use HasFactory;
    use HasTranslations;
    use LogsActivity;

    protected $table = 'dashed__metadata';

    protected static $logFillable = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public $translatable = [
        'title',
        'description',
        'image',
    ];
}
