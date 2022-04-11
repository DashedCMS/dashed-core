<?php

namespace Qubiqx\QcommerceCore\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Qubiqx\QcommerceCore\Classes\Sites;
use Spatie\Activitylog\Traits\LogsActivity;

class Customsetting extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $fillable = ['name', 'value', 'site_id', 'locale'];

    protected $table = 'qcommerce__custom_settings';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
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
