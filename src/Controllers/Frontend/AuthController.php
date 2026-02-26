<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;

class AuthController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect(route('dashed.frontend.account'))->with('success', Translation::get('already-logged-in', 'login', 'You are already logged in'));
        }

        if (View::exists(config('dashed-core.site_theme', 'dashed') . '.auth.login')) {
            seo()->metaData('metaTitle', Translation::get('login-page-meta-title', 'login', 'Login'));
            seo()->metaData('metaDescription', Translation::get('login-page-meta-description', 'login', 'Login to your account'));

            return view('dashed-core::layouts.livewire-master', [
                'livewireComponent' => Login::class,
            ]);

            return view(config('dashed-core.site_theme', 'dashed') . '.auth.login');
        } else {
            return $this->pageNotFound();
        }
    }

    public function logout()
    {
        if (! Auth::check()) {
            return redirect(AccountHelper::getLoginUrl())->with('success', Translation::get('already-logged-out', 'login', 'You are already logged out'));
        }

        Auth::logout();

        return redirect(AccountHelper::getLoginUrl())->with('success', Translation::get('succesfully-logged-out', 'login', 'You are logged out!'));
    }

    public function forgotPassword()
    {
        if (Auth::check()) {
            return redirect(route('dashed.frontend.account'))->with('success', Translation::get('already-logged-in', 'login', 'You are already logged in'));
        }

        if (View::exists(config('dashed-core.site_theme', 'dashed') . '.auth.forgot-password')) {
            seo()->metaData('metaTitle', Translation::get('forgot-password-page-meta-title', 'login', 'Forgot password'));
            seo()->metaData('metaDescription', Translation::get('forgot-password-page-meta-description', 'login', 'Forgot your password?'));

            return view('dashed-core::layouts.livewire-master', [
                'livewireComponent' => ForgotPassword::class,
            ]);

            return view(config('dashed-core.site_theme', 'dashed') . '.auth.forgot-password');
        } else {
            return $this->pageNotFound();
        }
    }

    public function resetPassword($passwordResetToken)
    {
        if (Auth::check()) {
            return redirect(route('dashed.frontend.account'))->with('success', Translation::get('already-logged-in', 'login', 'You are already logged in'));
        }

        if (View::exists(config('dashed-core.site_theme', 'dashed') . '.auth.reset-password')) {
            seo()->metaData('metaTitle', Translation::get('reset-password-page-meta-title', 'login', 'Reset password'));
            seo()->metaData('metaDescription', Translation::get('reset-password-page-meta-description', 'login', 'Reset your password'));

            $user = User::where('password_reset_token', $passwordResetToken)->first();

            if (! $user) {
                return redirect(route('dashed.frontend.auth.forgot-password'))->with('success', Translation::get('reset-token-invalid', 'login', 'The token that was provided is invalid'));
            }

            View::share('user', $user);
            View::share('passwordResetToken', $passwordResetToken);

            return view('dashed-core::layouts.livewire-master', [
                'livewireComponent' => ResetPassword::class,
                'parameters' => [
                    'passwordResetToken' => $passwordResetToken,
                    'user' => $user,
                ],
            ]);

            return view(config('dashed-core.site_theme', 'dashed') . '.auth.reset-password');
        } else {
            return $this->pageNotFound();
        }
    }
}
