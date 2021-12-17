<?php

namespace Qubiqx\QcommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Qubiqx\Qcommerce\Classes\Sites;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Customsetting extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;

    protected static $logFillable = true;

    protected $fillable = ['name', 'value', 'site_id', 'locale'];

    protected $table = 'qcommerce__custom_settings';

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Manipulations::FIT_CROP, 300, 300)
            ->nonQueued();
    }

    public static function get($name, $siteId = null, $default = null, $locale = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'];
        }

        return Cache::tags(['custom-settings', "custom-settings-$name"])->rememberForever("$name-$siteId-$locale", function () use ($name, $siteId, $default, $locale) {
            if (! Schema::hasTable('qcommerce__custom_settings')) {
                return;
            }

            $customSetting = self::where('name', $name)->where('site_id', $siteId)->where('locale', $locale)->first();
            if ($customSetting && $customSetting->value !== null) {
                return $customSetting->value;
            } else {
                return $default;
            }
        });
    }

    public static function set($name, $value, $siteId = null, $locale = null)
    {
        if (! $siteId) {
            $siteId = config('qcommerce.sites')[0]['id'];
        }

        self::updateOrCreate(
            [
                'name' => $name,
                'site_id' => $siteId,
                'locale' => $locale,
            ],
            ['value' => $value]
        );

        Cache::tags(["custom-settings-$name"])->flush();
    }

    public function scopeThisSite($query)
    {
        $query->where('site_id', config('qcommerce.currentSite'));
    }
}
