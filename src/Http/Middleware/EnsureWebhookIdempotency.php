<?php

namespace Dashed\DashedCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Webhook idempotency middleware. Populated in Task 10 of Bundle 2.
 *
 * Route usage:
 *
 *     Route::post('/exchange', [TransactionController::class, 'exchange'])
 *         ->middleware('webhook.idempotency:auto');
 *
 * The `auto` parameter triggers provider auto-detection via the registry
 * declared in `config/webhooks.php`. Named providers (`mollie`, `paynl`,
 * `multisafepay`) skip detection and use the matching extractor directly.
 */
class EnsureWebhookIdempotency
{
    public function handle(Request $request, Closure $next, string $provider = 'auto')
    {
        // Task 10 lands the real implementation. For now pass through so the
        // alias registration in DashedCoreServiceProvider doesn't break routes.
        return $next($request);
    }
}
