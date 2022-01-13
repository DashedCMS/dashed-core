<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Qubiqx\QcommerceCore\Controllers\Frontend\AccountController;
use Qubiqx\QcommerceCore\Controllers\Frontend\AuthController;
use Qubiqx\QcommerceCore\Controllers\Frontend\FormController;
use Qubiqx\QcommerceCore\Controllers\Frontend\FrontendController;
use Qubiqx\QcommerceCore\Middleware\AuthMiddleware;
use Qubiqx\QcommerceCore\Middleware\FrontendMiddleware;
use Qubiqx\QcommerceCore\Middleware\GuestMiddleware;
use Qubiqx\QcommerceCore\Models\Translation;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['web', FrontendMiddleware::class, LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class],
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

        //Form routes
        Route::post('/form/post', [FormController::class, 'store'])->name('qcommerce.frontend.forms.store');

    }
);

Route::fallback([FrontendController::class, 'index'])->middleware(['web', FrontendMiddleware::class, LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class, LaravelLocalizationViewPath::class])->name('qcommerce.frontend.general.index')->where('slug', '.*');
