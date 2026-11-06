<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

class GlobalBlock extends Model
{
    use HasTranslations;
    use SoftDeletes;
    use LogsActivity;

    public const CONTENT_VERSION_KEY = 'dashed:global-blocks:version';

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
        static::saved(fn () => self::invalidateDependentCaches());
        static::deleted(fn () => self::invalidateDependentCaches());
    }

    /**
     * Een globaal blok rendert binnen de pagina die hem gebruikt, dus twee caches
     * hangen ervan af: de responsecache per site en de blokcache, waarvan de
     * sleutel alleen de pagina kent en daarom deze versie meedraagt. Dit verving
     * een cache:clear, die ook locks, limieten en alles van andere pakketten wiste.
     */
    public static function invalidateDependentCaches(): void
    {
        Cache::forever(self::CONTENT_VERSION_KEY, (string) now()->getPreciseTimestamp(3));

        foreach (Sites::getSites() as $site) {
            CacheInvalidator::flushSite($site['id']);
        }
    }

    public static function contentVersion(): string
    {
        return (string) Cache::get(self::CONTENT_VERSION_KEY, '0');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
