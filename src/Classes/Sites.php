<?php

namespace Qubiqx\QcommerceCore\Classes;

class Sites
{
    public static function getActive()
    {
        return env('QCOMMERCE_SITE_ID', cms()->builder('sites')[0]['id']);
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
        if (!$siteId) {
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
        if (!$siteId) {
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

        return $locales;
    }
}
