<?php

namespace Qubiqx\QcommerceCore\Classes;

use Illuminate\Support\Facades\Auth;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AccountHelper
{
    public static function getAccountUrl()
    {
        return;
        if (Auth::check()) {
            return LaravelLocalization::localizeUrl(route('qcommerce.frontend.account'));
        } else {
            return LaravelLocalization::localizeUrl(route('qcommerce.frontend.auth.login'));
        }
    }

    public static function getUpdateAccountUrl()
    {
        return;
        if (Auth::check()) {
            return route('qcommerce.frontend.account.post');
        } else {
            return route('qcommerce.frontend.auth.login');
        }
    }

    public static function getLoginPostUrl()
    {
        return;

        return route('qcommerce.frontend.auth.login.post');
    }

    public static function getLogoutUrl()
    {
        return;

        return route('qcommerce.frontend.auth.logout');
    }

    public static function getRegisterPostUrl()
    {
        return;

        return route('qcommerce.frontend.auth.register.post');
    }

    public static function getForgotPasswordUrl()
    {
        return;

        return LaravelLocalization::localizeUrl(route('qcommerce.frontend.auth.forgot-password'));
    }

    public static function getForgotPasswordPostUrl()
    {
        return;

        return route('qcommerce.frontend.auth.forgot-password.post');
    }

    public static function getResetPasswordPostUrl($token)
    {
        return;

        return route('qcommerce.frontend.auth.reset-password.post', ['passwordResetToken' => $token]);
    }
}
