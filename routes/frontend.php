<?php

use Illuminate\Support\Facades\Route;
use Dashed\DashedCore\Middleware\AuthMiddleware;
use Dashed\DashedCore\Middleware\GuestMiddleware;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Middleware\FrontendMiddleware;
use Dashed\DashedCore\Controllers\Frontend\AuthController;
use Dashed\LaravelLocalization\Facades\LaravelLocalization;
use Dashed\DashedCore\Controllers\Frontend\AccountController;
use Dashed\DashedCore\Controllers\Frontend\FrontendController;
use Dashed\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Dashed\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Dashed\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;

if (config('dashed-core.default_auth_pages_enabled', true)) {

    Route::group(
        [
            'prefix' => LaravelLocalization::setLocale(),
            'middleware' => array_merge(['web', FrontendMiddleware::class, \Dashed\DashedCore\Middleware\LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class], cms()->builder('frontendMiddlewares')),
        ],
        function () {
            //Auth routes

            Route::get('/' . Translation::get('logout-slug', 'slug', 'logout'), [AuthController::class, 'logout'])->name('dashed.frontend.auth.logout');
            Route::group([
                'middleware' => [GuestMiddleware::class],
            ], function () {
//                Route::get('/'.Translation::get('login-slug', 'slug', 'login'), [AuthController::class, 'login'])->name('dashed.frontend.auth.login');
//                Route::post('/'.Translation::get('login-slug', 'slug', 'login'), [AuthController::class, 'loginPost'])->name('dashed.frontend.auth.login.post');
//                Route::post('/'.Translation::get('register-slug', 'slug', 'register'), [AuthController::class, 'login'])->name('dashed.frontend.auth.register');
//                Route::post('/'.Translation::get('register-slug', 'slug', 'register'), [AuthController::class, 'registerPost'])->name('dashed.frontend.auth.register.post');

//                Route::get('/'.Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [AuthController::class, 'forgotPassword'])->name('dashed.frontend.auth.forgot-password');
//                Route::post('/'.Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [AuthController::class, 'forgotPasswordPost'])->name('dashed.frontend.auth.forgot-password.post');
//                Route::get('/'.Translation::get('reset-password-slug', 'slug', 'reset-password').'/{passwordResetToken}', [AuthController::class, 'resetPassword'])->name('dashed.frontend.auth.reset-password');
//                Route::post('/'.Translation::get('reset-password-slug', 'slug', 'reset-password').'/{passwordResetToken}', [AuthController::class, 'resetPasswordPost'])->name('dashed.frontend.auth.reset-password.post');
            });

            Route::group([
                'middleware' => [AuthMiddleware::class],
            ], function () {
                //Account routes
                Route::prefix('/' . Translation::get('account-slug', 'slug', 'account'))->group(function () {
//                    Route::get('/', [AccountController::class, 'account'])->name('dashed.frontend.account');
//                    Route::post('/', [AccountController::class, 'accountPost'])->name('dashed.frontend.account.post');
                });
            });
        }
    );
}

Route::fallback([FrontendController::class, 'index'])
    ->middleware(array_merge(['web', FrontendMiddleware::class, \Dashed\DashedCore\Middleware\LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class], cms()->builder('frontendMiddlewares')))
    ->name('dashed.frontend.general.index')
    ->where('slug', '.*');
