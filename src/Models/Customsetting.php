<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Customsetting extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__custom_settings';

    protected $casts = [
        'json' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public static function booted()
    {
        static::saved(function (Customsetting $customsetting) {
            Cache::forget('dashed__custom_settings_table_exists');
            foreach (Sites::getSites() as $site) {
                foreach (Locales::getLocalesArray() as $key => $locale) {
                    Cache::forget($customsetting->name . '-' . $site['id'] . '-' . $key);
                }
            }
        });
    }

    public static function get(string $name, ?string $siteId = null, null|string|array $default = null, ?string $locale = null, string $type = 'default')
    {
        $tableExists = Cache::remember('dashed__custom_settings_table_exists', 60, function () {
            return Schema::hasTable('dashed__custom_settings');
        });

        if (! $tableExists) {
            return $default;
        }

        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'];
        }

        $value = Cache::rememberForever("$name-$siteId-$locale", function () use ($name, $siteId, $default, $locale) {
            //Cannot use this because this fails emails etc
            //        if (app()->runningInConsole()) {
            //            return $default;
            //        }

            $customSetting = self::where('name', $name)->where('site_id', $siteId)->where('locale', $locale)->first();
            if ($customSetting && $customSetting->json !== null) {
                return $customSetting->json;
            } elseif ($customSetting && $customSetting->value !== null) {
                if ($customSetting->value == '[]') {
                    return [];
                }

                return $customSetting->value;
            } else {
                if ($default == '[]') {
                    return [];
                }

                return $default;
            }
        });

        if ($type == 'link') {
            $value = linkHelper()->getUrl($value);
        }

        return $value;
    }

    public static function set(string $name, null|array|string $value, ?string $siteId = null, ?string $locale = null)
    {
        if (! $siteId) {
            $siteId = Sites::getSites()[0]['id'];
        }

        if ($value && is_array($value)) {
            $valueField = 'json';
        } else {
            $valueField = 'value';
        }

        self::updateOrCreate(
            [
                'name' => $name,
                'site_id' => $siteId,
                'locale' => $locale,
            ],
            [$valueField => $value]
        );

        Cache::forget("$name-$siteId-$locale");
        foreach (Sites::getSites() as $site) {
            foreach (Locales::getLocalesArray() as $locale) {
                Cache::forget("$name-" . $site['id'] . "-$locale");
            }
        }
    }

    public static function reset(string $name, ?string $siteId = null, ?string $locale = null)
    {
        if (! $siteId) {
            $siteId = Sites::getSites()[0]['id'];
        }

        self::where('name', $name)->where('site_id', $siteId)->where('locale', $locale)->delete();

        Cache::forget("$name-$siteId-$locale");
        foreach (Sites::getSites() as $site) {
            foreach (Locales::getLocalesArray() as $locale) {
                Cache::forget("$name-" . $site['id'] . "-$locale");
            }
        }
    }

    public function scopeThisSite($query)
    {
        $query->where('site_id', Sites::getActive());
    }
}
