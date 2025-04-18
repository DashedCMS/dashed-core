<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\App;
use Dashed\LaravelLocalization\Facades\LaravelLocalization;

class Locales
{
    public static function getLocales(): array
    {
        $allLocales = \Dashed\LaravelLocalization\Facades\LaravelLocalization::getLocalesOrder();
        $locales = [];
        foreach ($allLocales as $key => $locale) {
            $locale['id'] = $key;
            $locales[] = $locale;
        }

        return $locales;
    }

    public static function getLocalesForSite(?string $site = null): array
    {
        if (!$site) {
            $site = Sites::getActive();
        }

        $activeLocales = collect(cms()->builder('sites'))->where('id', $site)->first();

        $allLocales = \Dashed\LaravelLocalization\Facades\LaravelLocalization::getLocalesOrder();
        $locales = [];

        foreach ($allLocales as $key => $locale) {
            if (in_array($key, $activeLocales['locales'])) {
                $locale['id'] = $key;
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    public static function getLocalesArray(): array
    {
        $locales = [];
        foreach (self::getLocales() as $locale) {
            $locales[$locale['id']] = $locale['name'];
        }

        return $locales;
    }

    public static function getLocalesArrayWithoutCurrent(?string $currentLocale = null): array
    {
        $locales = self::getLocalesArray();

        if (!$currentLocale) {
            $currentLocale = LaravelLocalization::getCurrentLocale();
        }

        foreach ($locales as $locale => $name) {
            if ($locale == $currentLocale) {
                unset($locales[$locale]);
            }
        }

        return $locales;
    }

    public static function getFirstLocale()
    {
        return self::getLocales()[0];
    }

    public static function getLocale($locale = null)
    {
        if (!$locale) {
            return self::getFirstLocale();
        } else {
            foreach (self::getLocales() as $allLocale) {
                if ($allLocale['id'] == $locale) {
                    return $allLocale;
                }
            }
        }

        return self::getFirstLocale();
    }

    public static function setLocale($locale)
    {
        App::setLocale($locale);
        LaravelLocalization::setLocale($locale);
    }
}
