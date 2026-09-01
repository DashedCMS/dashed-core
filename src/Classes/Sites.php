<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\URL;
use Dashed\DashedCore\Models\Customsetting;

class Sites
{
    public static function getActive()
    {
        if (count(cms()->builder('sites'))) {
            return config('dashed-core.dashed_site_id', cms()->builder('sites')[0]['id']);
        } else {
            return '';
        }
    }

    public static function getSites()
    {
        return cms()->builder('sites');
    }

    public static function getAmountOfSites()
    {
        return count(self::getSites());
    }

    public static function getFirstSite()
    {
        return cms()->builder('sites')[0];
    }

    public static function get($siteId = null)
    {
        if (! $siteId) {
            return self::getFirstSite();
        }

        foreach (self::getSites() as $site) {
            if ($site['id'] == $siteId) {
                $site['locales'] = self::getLocales($site['id']);

                return $site;
            }
        }
    }

    public static function getLocales($siteId = null)
    {
        if (! $siteId) {
            $site = self::getFirstSite();
        } else {
            foreach (self::getSites() as $allSite) {
                if ($allSite['id'] == $siteId) {
                    $site = $allSite;
                }
            }
        }

        $allLocales = Locales::getLocales();
        $locales = [];
        foreach ($allLocales as $locale) {
            if (in_array($locale['id'], $site['locales'])) {
                $locales[] = $locale;
            }
        }

        return collect($locales);
    }

    /**
     * Het geconfigureerde domein van de site: site_url, anders APP_URL. Nooit
     * de Host-header van het verzoek, want die bepaalt de afzender.
     */
    public static function rootUrl($siteId = null): string
    {
        return rtrim((string) (Customsetting::get('site_url', $siteId) ?: config('app.url')), '/');
    }

    /**
     * Absolute URL op het domein van de site, voor links die de deur uit gaan
     * (mails). Een al-absolute URL houdt pad, query en fragment maar krijgt
     * schema en host van de site.
     */
    public static function url(string $path = '', $siteId = null): string
    {
        if (preg_match('#^https?://#i', $path)) {
            $parts = parse_url($path);
            $path = ($parts['path'] ?? '/')
                . (isset($parts['query']) ? '?' . $parts['query'] : '')
                . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        }

        return self::rootUrl($siteId) . '/' . ltrim($path, '/');
    }

    /**
     * Laat url()/route()/URL::signedRoute() tijdens de callback het domein van
     * de site gebruiken in plaats van de Host-header van het verzoek. Daarna
     * gaat de root terug naar "niet vastgezet".
     */
    public static function withForcedRootUrl(callable $callback, $siteId = null): mixed
    {
        URL::forceRootUrl(self::rootUrl($siteId));

        try {
            return $callback();
        } finally {
            URL::forceRootUrl(null);
        }
    }
}
