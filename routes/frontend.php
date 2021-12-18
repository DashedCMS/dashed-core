<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Qubiqx\QcommerceCore\Controllers\Frontend\FrontendController;
use Qubiqx\QcommerceCore\Middleware\GuestMiddleware;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceCore\Models\Translation;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['web', FrontendMiddleware::class, LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class],
    ],
    function () {
        if (Customsetting::get('checkout_account') != 'disabled') {
            //Auth routes

            Route::get('/' . Translation::get('logout-slug', 'slug', 'logout'), [FrontendAuthController::class, 'logout'])->name('qcommerce.frontend.auth.logout');
            Route::group([
                'middleware' => [GuestMiddleware::class],
            ], function () {
//                Route::get('/' . Translation::get('login-slug', 'slug', 'login'), [FrontendAuthController::class, 'login'])->name('qcommerce.frontend.auth.login');
//                Route::post('/' . Translation::get('login-slug', 'slug', 'login'), [FrontendAuthController::class, 'loginPost'])->name('qcommerce.frontend.auth.login.post');
//                Route::post('/' . Translation::get('register-slug', 'slug', 'register'), [FrontendAuthController::class, 'login'])->name('qcommerce.frontend.auth.register');
//                Route::post('/' . Translation::get('register-slug', 'slug', 'register'), [FrontendAuthController::class, 'registerPost'])->name('qcommerce.frontend.auth.register.post');

//                Route::get('/' . Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [FrontendAuthController::class, 'forgotPassword'])->name('qcommerce.frontend.auth.forgot-password');
//                Route::post('/' . Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [FrontendAuthController::class, 'forgotPasswordPost'])->name('qcommerce.frontend.auth.forgot-password.post');
//                Route::get('/' . Translation::get('reset-password-slug', 'slug', 'reset-password') . '/{passwordResetToken}', [FrontendAuthController::class, 'resetPassword'])->name('qcommerce.frontend.auth.reset-password');
//                Route::post('/' . Translation::get('reset-password-slug', 'slug', 'reset-password') . '/{passwordResetToken}', [FrontendAuthController::class, 'resetPasswordPost'])->name('qcommerce.frontend.auth.reset-password.post');
            });
        }

        //Form routes
//        Route::post('/form/post', [FrontendFormController::class, 'store'])->name('qcommerce.frontend.forms.store');

        Route::get('{slug?}', [FrontendController::class, 'index'])->name('qcommerce.frontend.general.index')->where('slug', '.*');
    }
);
