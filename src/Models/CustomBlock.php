<?php

namespace Qubiqx\QcommerceCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomBlock extends Model
{
    use LogsActivity;
    use HasFactory;
    use HasTranslations;

    protected $table = 'qcommerce__custom_blocks';

    protected static $logFillable = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public $translatable = [
        'blocks',
    ];
}
