<?php

use Illuminate\Support\Facades\Route;
use Qubiqx\QcommerceCore\Models\Translation;
use Qubiqx\QcommerceCore\Middleware\AuthMiddleware;
use Qubiqx\QcommerceCore\Middleware\GuestMiddleware;
use Qubiqx\QcommerceCore\Middleware\FrontendMiddleware;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Qubiqx\QcommerceCore\Controllers\Frontend\AuthController;
use Qubiqx\QcommerceCore\Controllers\Frontend\AccountController;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Qubiqx\QcommerceCore\Controllers\Frontend\FrontendController;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => array_merge(['web', FrontendMiddleware::class, LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class], cms()->builder('frontendMiddlewares')),
    ],
    function () {
        //Auth routes

        if (!app()->runningInConsole()) {
            Route::get('/' . Translation::get('logout-slug', 'slug', 'logout'), [AuthController::class, 'logout'])->name('qcommerce.frontend.auth.logout');
            Route::group([
                'middleware' => [GuestMiddleware::class],
            ], function () {
                Route::get('/' . Translation::get('login-slug', 'slug', 'login'), [AuthController::class, 'login'])->name('qcommerce.frontend.auth.login');
                Route::post('/' . Translation::get('login-slug', 'slug', 'login'), [AuthController::class, 'loginPost'])->name('qcommerce.frontend.auth.login.post');
                Route::post('/' . Translation::get('register-slug', 'slug', 'register'), [AuthController::class, 'login'])->name('qcommerce.frontend.auth.register');
                Route::post('/' . Translation::get('register-slug', 'slug', 'register'), [AuthController::class, 'registerPost'])->name('qcommerce.frontend.auth.register.post');

                Route::get('/' . Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [AuthController::class, 'forgotPassword'])->name('qcommerce.frontend.auth.forgot-password');
                Route::post('/' . Translation::get('forgot-password-slug', 'slug', 'forgot-password'), [AuthController::class, 'forgotPasswordPost'])->name('qcommerce.frontend.auth.forgot-password.post');
                Route::get('/' . Translation::get('reset-password-slug', 'slug', 'reset-password') . '/{passwordResetToken}', [AuthController::class, 'resetPassword'])->name('qcommerce.frontend.auth.reset-password');
                Route::post('/' . Translation::get('reset-password-slug', 'slug', 'reset-password') . '/{passwordResetToken}', [AuthController::class, 'resetPasswordPost'])->name('qcommerce.frontend.auth.reset-password.post');
            });

            Route::group([
                'middleware' => [AuthMiddleware::class],
            ], function () {
                //Account routes
                Route::prefix('/' . Translation::get('account-slug', 'slug', 'account'))->group(function () {
                    Route::get('/', [AccountController::class, 'account'])->name('qcommerce.frontend.account');
                    Route::post('/', [AccountController::class, 'accountPost'])->name('qcommerce.frontend.account.post');
                });
            });
        }
    }
);

Route::fallback([FrontendController::class, 'index'])->middleware(array_merge(['web', FrontendMiddleware::class, LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class], cms()->builder('frontendMiddlewares')))->name('qcommerce.frontend.general.index')->where('slug', '.*');
