<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

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
