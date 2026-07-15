<?php

// API-routes voor dashed-core.

use Illuminate\Support\Facades\Route;
use Dashed\DashedCore\Http\Controllers\PostmarkWebhookController;

Route::post('/dashed/webhooks/postmark', PostmarkWebhookController::class)
    ->name('dashed.webhooks.postmark');
