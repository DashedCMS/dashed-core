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

    /**
     * Runtime cache voor alle get() calls binnen één request.
     *
     * [
     *   'siteId|localeKey' => [
     *      'setting_name' => value,
     *      ...
     *   ],
     * ]
     */
    protected static array $runtimeContextCache = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public static function booted()
    {
        static::saved(function (Customsetting $customsetting) {
            // Table-exists cache resetten
            Cache::forget('dashed__custom_settings_table_exists');

            // Context-caches flushen voor alle sites/locales
            foreach (Sites::getSites() as $site) {
                // null-locale
                Cache::forget(static::cacheKey($site['id'], null));

                foreach (Locales::getLocalesArray() as $key => $locale) {
                    $localeId = is_array($locale) ? ($locale['id'] ?? $key) : $key;
                    Cache::forget(static::cacheKey($site['id'], $localeId));

                    // Oud per-setting cache key-pattern voor backwards compat
                    Cache::forget($customsetting->name . '-' . $site['id'] . '-' . $localeId);
                }
            }

            // Runtime cache voor deze request leegmaken
            static::$runtimeContextCache = [];
        });
    }

    public static function get(
        string $name,
        ?string $siteId = null,
        null|string|array $default = null,
        ?string $locale = null,
        string $type = 'default',
        bool $disableCache = false
    ) {
        // Check of de table bestaat (voor vroege boot / artisankommandos)
        $tableExists = Cache::remember('dashed__custom_settings_table_exists', 60, function () {
            return Schema::hasTable('dashed__custom_settings');
        });

        if (! $tableExists) {
            return $default;
        }

        // Site & locale normaliseren
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'] ?? null;
        }

        $contextKey = static::contextKey($siteId, $locale);

        // Cache forceren te verversen (zelfde gedrag als oude implementatie)
        if ($disableCache) {
            Cache::forget(static::cacheKey($siteId, $locale));
            unset(static::$runtimeContextCache[$contextKey]);
        }

        // 1) Runtime context cache (per request)
        if (isset(static::$runtimeContextCache[$contextKey])) {
            $settingsForContext = static::$runtimeContextCache[$contextKey];
        } else {
            // 2) Laravel cache (per site + locale)
            $settingsForContext = Cache::rememberForever(
                static::cacheKey($siteId, $locale),
                function () use ($siteId, $locale) {
                    return static::loadContextSettings($siteId, $locale);
                }
            );

            static::$runtimeContextCache[$contextKey] = $settingsForContext;
        }

        // 3) Waarde ophalen of default
        if (array_key_exists($name, $settingsForContext)) {
            $value = $settingsForContext[$name];
        } else {
            // Zelfde default-gedrag als eerst
            if ($default === '[]') {
                $value = [];
            } else {
                $value = $default;
            }
        }

        // Link-type support
        if ($type === 'link' && $value !== null) {
            $value = linkHelper()->getUrl($value);
        }

        return $value;
    }

    public static function set(string $name, null|array|string $value, ?string $siteId = null, ?string $locale = null)
    {
        if (! $siteId) {
            $siteId = Sites::getSites()[0]['id'];
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'] ?? null;
        }

        $valueField = is_array($value) ? 'json' : 'value';

        static::updateOrCreate(
            [
                'name'    => $name,
                'site_id' => $siteId,
                'locale'  => $locale,
            ],
            [$valueField => $value]
        );

        // Context flushen
        static::flushFor($siteId, $locale);
    }

    public static function reset(string $name, ?string $siteId = null, ?string $locale = null)
    {
        if (! $siteId) {
            $siteId = Sites::getSites()[0]['id'];
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'] ?? null;
        }

        static::where('name', $name)
            ->where('site_id', $siteId)
            ->where('locale', $locale)
            ->delete();

        static::flushFor($siteId, $locale);
    }

    public function scopeThisSite($query)
    {
        $query->where('site_id', Sites::getActive());
    }

    /*
     *  Helpers
     */

    protected static function cacheKey(string $siteId, ?string $locale = null): string
    {
        return 'customsettings:' . $siteId . ':' . ($locale ?? 'null');
    }

    protected static function contextKey(string $siteId, ?string $locale = null): string
    {
        return $siteId . '|' . ($locale ?? 'null');
    }

    /**
     * Laadt alle settings voor een site+locale uit de DB
     * en zet ze om naar een simpel [name => value] array.
     */
    protected static function loadContextSettings(string $siteId, ?string $locale = null): array
    {
        $query = static::query()
            ->where('site_id', $siteId);

        if ($locale !== null) {
            $query->where('locale', $locale);
        } else {
            $query->whereNull('locale');
        }

        $rows = $query->get();

        $settings = [];

        foreach ($rows as $row) {
            if ($row->json !== null) {
                $settings[$row->name] = $row->json;
            } elseif ($row->value !== null) {
                if ($row->value === '[]') {
                    $settings[$row->name] = [];
                } else {
                    $settings[$row->name] = $row->value;
                }
            }
        }

        return $settings;
    }

    /**
     * Wis cache + runtime cache voor een bepaalde site+locale.
     */
    public static function flushFor(?string $siteId = null, ?string $locale = null): void
    {
        if (! $siteId) {
            $siteId = Sites::getSites()[0]['id'];
        }

        if ($locale && is_array($locale)) {
            $locale = $locale['id'] ?? null;
        }

        Cache::forget(static::cacheKey($siteId, $locale));

        $contextKey = static::contextKey($siteId, $locale);
        unset(static::$runtimeContextCache[$contextKey]);
    }
}
