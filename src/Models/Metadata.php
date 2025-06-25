<?php

namespace Dashed\DashedCore\Models;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedEcommerceCore\Events\Products\ProductCreatedEvent;
use Dashed\DashedEcommerceCore\Events\Products\ProductSavedEvent;
use Dashed\DashedEcommerceCore\Events\Products\ProductUpdatedEvent;
use Dashed\DashedEcommerceCore\Jobs\UpdateProductInformationJob;
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

    protected static function booted()
    {
        static::saved(function ($metadata) {
            foreach(Locales::getActivatedLocalesFromSites() as $locale) {
                $metadata->setTranslation('title', $locale, str($metadata->getTranslation('title', $locale))->limit(70, '')->toString());
                $metadata->setTranslation('description', $locale, str($metadata->getTranslation('description', $locale))->limit(170, '')->toString());
            }
            $metadata->saveQuietly();
        });
    }

    public $translatable = [
        'title',
        'description',
        'image',
    ];
}
