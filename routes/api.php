<?php

use Dashed\DashedCore\Performance\WebVitals\VitalsController;
use Illuminate\Support\Facades\Route;

Route::post('/_dashed/perf/vitals', [VitalsController::class, 'store'])
    ->middleware(['throttle:60,1'])
    ->name('dashed.perf.vitals');
