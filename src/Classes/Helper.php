<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\UrlHistory;
use Illuminate\Support\Facades\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Helper
{
    public static function urlIsActive($url, $exact = false)
    {
        $url = url($url);
        if ($url == Request::url() || url(str_replace(url('/'), '', $url)) == Request::url()) {
            return true;
        }

        if ($exact) {
            return false;
        }

        if (strpos(Request::url(), $url) !== false) {
            return true;
        }

        return false;
    }

    public static function getProfilePicture($email)
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email)));
    }

    public static function getAdminUrl()
    {
        return url(config('filament.path'));
    }

    public static function getLocalUrl($url)
    {
        return LaravelLocalization::localizeUrl($url);
    }

    public static function getCurrentUrlInLocale($locale, $url = null, $model = null)
    {
        if ($model) {
            return $model->getUrl($locale);
        } else {
            $urlHistory = UrlHistory::where('url', $url ?: str(request()->url())->replace(url('/'), ''))->where('locale', app()->getLocale())->where('site_id', Sites::getActive())->first();
            if ($urlHistory && $urlHistory->previous_url) {
                return $urlHistory->previous_url;
            }
        }

        if (! $url) {
            $url = '/'.$locale;
        }

        return $url;

        //When using below, it removes the defaultLocale from URL, which makes it impossible to switch
        return LaravelLocalization::getLocalizedURL($locale, $url);
    }
}
