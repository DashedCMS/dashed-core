<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store raw payloads
    |--------------------------------------------------------------------------
    |
    | When true, EnsureWebhookIdempotency stores the raw request body hash and
    | optionally the body itself in dashed__webhook_log for later replay via
    | `dashed:webhook:replay`. Default OFF because Mollie/PayNL payloads can
    | include card metadata you don't want sitting in the application DB.
    |
    */
    'store_payload' => env('DASHED_WEBHOOK_STORE_PAYLOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | dashed__webhook_log rows older than this many days are pruned by a
    | scheduled job. Long enough to debug post-incident, short enough to keep
    | the table from becoming a write-only graveyard.
    |
    */
    'retention_days' => (int) env('DASHED_WEBHOOK_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Provider extractors
    |--------------------------------------------------------------------------
    |
    | Maps a provider slug to a callable returning the canonical `event_id`
    | for that provider's webhook payload. Populated by per-provider tasks
    | (Mollie/PayNL/Multisafepay). Empty placeholders are valid — the
    | extractor registry treats them as "no fingerprint available".
    |
    */
    'providers' => [
        'mollie' => null,
        'paynl' => null,
        'multisafepay' => null,
    ],
];
