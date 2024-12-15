<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedPages\Models\Page;
use Illuminate\Support\Facades\Auth;
use Dashed\DashedCore\Models\Customsetting;

class AccountHelper
{
    public static function getAccountUrl(): string
    {
        $pageId = auth()->check() ? Customsetting::get('account_page_id') : Customsetting::get('login_page_id');
        $page = Page::publicShowable()->where('id', $pageId)->first();

        return $page->getUrl() ?? '#';
    }

    //    public static function getUpdateAccountUrl()
    //    {
    //        if (Auth::check()) {
    //            return route('dashed.frontend.account.post');
    //        } else {
    //            return route('dashed.frontend.auth.login');
    //        }
    //    }

    //    public static function getLoginPostUrl()
    //    {
    //        return route('dashed.frontend.auth.login.post');
    //    }

    public static function getLogoutUrl()
    {
        return route('dashed.frontend.auth.logout');
    }

    //    public static function getRegisterPostUrl()
    //    {
    //        return route('dashed.frontend.auth.register.post');
    //    }

    public static function getForgotPasswordUrl()
    {
        $pageId = Customsetting::get('forgot_password_page_id');
        $page = Page::publicShowable()->where('id', $pageId)->first();

        return $page->getUrl() ?? '#';
    }

    public static function getResetPasswordUrl()
    {
        $pageId = Customsetting::get('reset_password_page_id');
        $page = Page::publicShowable()->where('id', $pageId)->first();

        return $page->getUrl() ?? '#';
    }

    //    public static function getForgotPasswordPostUrl()
    //    {
    //        return route('dashed.frontend.auth.forgot-password.post');
    //    }

    //    public static function getResetPasswordPostUrl($token)
    //    {
    //        return route('dashed.frontend.auth.reset-password.post', ['passwordResetToken' => $token]);
    //    }
}
