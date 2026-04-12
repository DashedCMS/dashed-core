<?php

use Illuminate\Support\Facades\Route;
use Dashed\DashedCore\Performance\WebVitals\VitalsController;

Route::post('/_dashed/perf/vitals', [VitalsController::class, 'store'])
    ->middleware(['throttle:60,1'])
    ->name('dashed.perf.vitals');
