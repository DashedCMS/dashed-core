<?php

namespace Dashed\DashedCore\Models;

use Dashed\DashedCore\Classes\Sites;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomsettingOld extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__custom_settings';

    public const CACHE_KEY = 'global_settings';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public static function booted()
    {
        static::saved(function () {
            cache()->forget(static::CACHE_KEY);
        });
    }

    public static function get($name, $siteId = null, $default = null, $locale = null)
    {
        self::warm();

        $settings = cache()->get(static::CACHE_KEY);

        if (! $settings) {
            return $default;
        }

        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'];
        }

        $setting = $settings->where('name', $name)->where('site_id', $siteId)->where('locale', $locale)->first();
        if ($setting && $setting->value !== null) {
            return $setting->value;
        } else {
            return $default;
        }

        return Cache::rememberForever("$name-$siteId-$locale", function () use ($name, $siteId, $default, $locale) {

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
            $siteId = Sites::getSites()[0]['id'];
        }

        self::updateOrCreate(
            [
                'name' => $name,
                'site_id' => $siteId,
                'locale' => $locale,
            ],
            ['value' => $value]
        );
    }

    public function scopeThisSite($query)
    {
        $query->where('site_id', Sites::getActive());
    }

    public static function warm(): void
    {
        $tableExists = Cache::rememberForever('dashed__custom_settings_table_exists', function () {
            return Schema::hasTable('dashed__custom_settings');
        });

        if (! $tableExists) {
            return;
        }

        if (! cache()->has(static::CACHE_KEY)) {
            cache()->forever(
                static::CACHE_KEY,
                $tableExists ? CustomsettingOld::all() : [],
            );
        }
    }
}
