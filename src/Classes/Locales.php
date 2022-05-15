<?php

namespace Qubiqx\QcommerceCore\Classes;

use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Locales
{
    public static function getLocales()
    {
        $allLocales = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalesOrder();
        $locales = [];
        foreach ($allLocales as $key => $locale) {
            $locale['id'] = $key;
            $locales[] = $locale;
        }

        return $locales;
    }

    public static function getFirstLocale()
    {
        return self::getLocales()[0];
    }

    public static function getLocale($locale = null)
    {
        if (! $locale) {
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
