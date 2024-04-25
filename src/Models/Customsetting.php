<?php

namespace Dashed\DashedCore\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Customsetting extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__custom_settings';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public static function get($name, $siteId = null, $default = null, $locale = null)
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

        return Cache::tags(['custom-settings', "custom-settings-$name"])->rememberForever("$name-$siteId-$locale", function () use ($name, $siteId, $default, $locale) {
            //Cannot use this because this fails emails etc
//        if (app()->runningInConsole()) {
//            return $default;
//        }

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

        Cache::tags(["custom-settings-$name"])->flush();
    }

    public function scopeThisSite($query)
    {
        $query->where('site_id', Sites::getActive());
    }
}
