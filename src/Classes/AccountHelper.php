<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Auth;
use Dashed\LaravelLocalization\Facades\LaravelLocalization;

class AccountHelper
{
    public static function getAccountUrl()
    {
        if (Auth::check()) {
            return LaravelLocalization::localizeUrl(route('dashed.frontend.account'));
        } else {
            return LaravelLocalization::localizeUrl(route('dashed.frontend.auth.login'));
        }
    }

    public static function getUpdateAccountUrl()
    {
        if (Auth::check()) {
            return route('dashed.frontend.account.post');
        } else {
            return route('dashed.frontend.auth.login');
        }
    }

    public static function getLoginPostUrl()
    {
        return route('dashed.frontend.auth.login.post');
    }

    public static function getLogoutUrl()
    {
        return route('dashed.frontend.auth.logout');
    }

    public static function getRegisterPostUrl()
    {
        return route('dashed.frontend.auth.register.post');
    }

    public static function getForgotPasswordUrl()
    {
        return LaravelLocalization::localizeUrl(route('dashed.frontend.auth.forgot-password'));
    }

    public static function getForgotPasswordPostUrl()
    {
        return route('dashed.frontend.auth.forgot-password.post');
    }

    public static function getResetPasswordPostUrl($token)
    {
        return route('dashed.frontend.auth.reset-password.post', ['passwordResetToken' => $token]);
    }
}
