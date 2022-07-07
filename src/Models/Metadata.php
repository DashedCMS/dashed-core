<?php

namespace Qubiqx\QcommerceCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Metadata extends Model
{
    use LogsActivity;
    use HasFactory;
    use HasTranslations;

    protected $table = 'qcommerce__metadata';

    protected static $logFillable = true;

    protected $fillable = [
        'image',
        'title',
        'description',
        'noindex',
        'sitemap_priority',
        'metadatable_type',
        'metadatable_id',
    ];

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
