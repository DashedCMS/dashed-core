# Webhook idempotency + BaseJob

Inbound payment webhooks (Mollie / PayNL / Multisafepay) are retried aggressively by the upstream provider. Dashed guards every inbound exchange with a DB-backed idempotency check so a duplicate delivery can never double-process the same event.

## EnsureWebhookIdempotency middleware

`Dashed\DashedCore\Http\Middleware\EnsureWebhookIdempotency`

Wire the middleware onto any webhook route. The `:auto` argument runs `WebhookProviderDetector` to fingerprint the request; pass an explicit provider slug to skip detection.

```php
Route::post('/exchange', ExchangeController::class)
    ->middleware('webhook.idempotency:auto');
```

What it does, in order:

1. Resolve a canonical `event_id` for this provider via `WebhookEventIdResolver`. If no extractor can fingerprint the body the request is passed through unguarded (preserves legacy behaviour).
2. `firstOrCreate` a `dashed__webhook_logs` row keyed on `UNIQUE(provider, event_id)`. The unique constraint serialises racing inserts at the DB layer.
3. Inside the same transaction take a `SELECT ... FOR UPDATE` on the row. If the row is already in `processing`, `succeeded` or `failed`, short-circuit with `204 No Content`.
4. Otherwise transition the row to `processing`, run the downstream controller, and finalise with `succeeded` (2xx response) or `failed` (4xx/5xx or thrown exception).

Set `DASHED_WEBHOOK_STORE_PAYLOAD=true` (config `webhooks.store_payload`) to persist the raw payload alongside the row — required for `dashed:webhook:replay` to work.

## Adding a new provider extractor

Extend `Dashed\DashedCore\Webhooks\WebhookProviderDetector` and add a branch to `detect()`. The fingerprint should be a cheap header/param check that uniquely identifies the provider; return the slug used to scope `WebhookLog` rows (`'mollie'`, `'paynl'`, `'multisafepay'`, ...).

Then teach `WebhookEventIdResolver` how to pull a stable `event_id` out of the request body for that provider (transaction id, signed event id, ...).

## Replay command

```bash
php artisan dashed:webhook:replay {provider} {event_id}
```

Replays a `failed` webhook by resetting its row to `received` and re-POSTing the stored payload to `dashed.frontend.checkout.exchange.post`. Refuses to run when:

- the row is not in `failed` state (replay is for failed rows only — never re-trigger `succeeded` or `processing`);
- no payload is stored (`DASHED_WEBHOOK_STORE_PAYLOAD` was off when the original webhook arrived);
- the stored payload is not valid JSON.

## HandlesQueueFailures trait + BaseJob

`Dashed\DashedCore\Jobs\Concerns\HandlesQueueFailures` standardises retry behaviour and failure reporting for every queue job in the CMS.

Compose it directly onto any `ShouldQueue` job, or extend `Dashed\DashedCore\Jobs\BaseJob` which bundles `Dispatchable + InteractsWithQueue + Queueable + SerializesModels + HandlesQueueFailures`.

Defaults:

| Property | Default | Meaning |
| --- | --- | --- |
| `$tries` | `3` | Attempts before terminal failure |
| `$timeout` | `90` | Seconds per attempt |
| `$backoff` | `[60, 300, 900]` | Delay (s) between retries |
| `$maxExceptions` | `2` | Distinct exceptions before giving up |
| `$notifyOnFailure` | `true` | Send `JobFailedMail` via `AdminNotifier` |
| `$notifyOnEveryAttempt` | `false` | If false, only the first failure per day notifies |

Terminal failures (after `$tries` exhausted) call `failed()` → `reportFailure()`:

1. Build structured log context (`site_id`, `locale`, `user_id`, `job`, `attempt`, `job_uuid`, plus anything from `extraLogContext()`).
2. Write to the `jobs` log channel (falls back to default).
3. Upsert a `dashed__job_failure_logs` row keyed on `(job_class, trace_hash, occurred_on)` — `trace_hash` is the first 12 chars of `sha1($e->getTraceAsString())`. This dedups identical exceptions to one row per day with a running `count`.
4. If this is the first failure of the day for that `(job_class, trace_hash)`, dispatch `JobFailedMail` via the `AdminNotifier` registry on the `mail` and `telegram` channels.
5. Always `report($e)` so the underlying exception still reaches the error reporter.

Override `extraLogContext()` to add job-specific fields (`order_id`, `cart_id`, ...) without touching the base context.
