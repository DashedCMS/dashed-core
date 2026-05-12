<?php

namespace Dashed\DashedCore\Http\Middleware;

use Closure;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Models\WebhookLog;
use Dashed\DashedCore\Webhooks\WebhookEventIdResolver;

/**
 * Webhook idempotency middleware.
 *
 * For inbound payment-gateway webhooks (Mollie/PayNL/Multisafepay), this
 * middleware guarantees that retries from the upstream provider don't
 * double-process the same event. It works in three steps:
 *
 *   1. Resolve a canonical `event_id` for this provider+request.
 *   2. firstOrCreate a `WebhookLog` row keyed on UNIQUE(provider, event_id).
 *      The unique constraint serialises concurrent inserts at the DB level.
 *   3. If the row already exists in any non-`received` state, short-circuit
 *      with 204 No Content. Otherwise transition to `processing`, run the
 *      downstream handler, and finalise with `succeeded` or `failed`.
 *
 * Route usage:
 *
 *     Route::post('/exchange', [...])
 *         ->middleware('webhook.idempotency:mollie');
 */
class EnsureWebhookIdempotency
{
    public function __construct(
        protected WebhookEventIdResolver $resolver,
    ) {
    }

    public function handle(Request $request, Closure $next, string $provider = 'auto'): Response
    {
        $eventId = $this->resolver->resolve($provider, $request);

        // If we cannot fingerprint the request at all (no extractor, no
        // body to hash), pass through unguarded. This preserves the
        // pre-middleware behaviour for malformed/empty webhooks rather
        // than 500-ing on a missing event_id.
        if (! is_string($eventId) || $eventId === '') {
            return $next($request);
        }

        $payloadHash = hash('sha256', (string) $request->getContent());
        $storePayload = (bool) config('webhooks.store_payload', false);

        $log = null;
        $shortCircuit = false;

        DB::transaction(function () use ($provider, $eventId, $payloadHash, $storePayload, $request, &$log, &$shortCircuit) {
            $log = WebhookLog::firstOrCreate(
                ['provider' => $provider, 'event_id' => $eventId],
                [
                    'payload_hash' => $payloadHash,
                    'received_at' => now(),
                    'status' => WebhookLog::STATUS_RECEIVED,
                    'payload' => $storePayload ? (string) $request->getContent() : null,
                ],
            );

            // Take a row-level lock to serialise concurrent requests that
            // race past the unique-index gate.
            $fresh = WebhookLog::query()
                ->where('provider', $provider)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($fresh && in_array($fresh->status, [
                WebhookLog::STATUS_SUCCEEDED,
                WebhookLog::STATUS_PROCESSING,
                WebhookLog::STATUS_FAILED,
            ], true)) {
                $shortCircuit = true;
                $log = $fresh;
                return;
            }

            $fresh->markProcessing();
            $log = $fresh;
        });

        if ($shortCircuit) {
            Log::info('[webhook.idempotency] short-circuit', [
                'provider' => $provider,
                'event_id' => $eventId,
                'existing_status' => $log->status ?? null,
            ]);

            return response()->noContent(204);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $log?->markFailed($e->getMessage());
            throw $e;
        }

        $status = $response instanceof Response ? $response->getStatusCode() : 200;
        if ($status >= 200 && $status < 300) {
            $log?->markSucceeded();
        } else {
            $log?->markFailed("HTTP {$status}");
        }

        return $response;
    }
}
