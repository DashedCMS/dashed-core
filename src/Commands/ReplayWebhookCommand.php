<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Dashed\DashedCore\Models\WebhookLog;

/**
 * Manually replay a webhook that previously landed in `failed` state.
 *
 * Usage:  php artisan dashed:webhook:replay <provider> <event_id>
 *
 * The command:
 *  - Looks up the row by (provider, event_id).
 *  - Refuses if the row is not in `failed` status (replay is for failed
 *    rows only — succeeded/processing rows must not be re-triggered).
 *  - Refuses if no payload is stored (requires DASHED_WEBHOOK_STORE_PAYLOAD=true
 *    to have been set when the original webhook was received).
 *  - Resets the row's status so the middleware lets the next request through.
 *  - POSTs the stored payload back to the `dashed.frontend.checkout.exchange`
 *    route so the same controller code path runs again.
 */
class ReplayWebhookCommand extends Command
{
    protected $signature = 'dashed:webhook:replay {provider} {event_id}';

    protected $description = 'Replay a failed webhook by re-POSTing its stored payload to the exchange route.';

    public function handle(): int
    {
        $provider = (string) $this->argument('provider');
        $eventId = (string) $this->argument('event_id');

        $row = WebhookLog::query()
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();

        if (! $row) {
            $this->error("No webhook_log row found for {$provider}/{$eventId}.");
            return self::FAILURE;
        }

        if ($row->status !== WebhookLog::STATUS_FAILED) {
            $this->error("Refusing to replay: row status is '{$row->status}', expected 'failed'.");
            return self::FAILURE;
        }

        if (! $row->payload) {
            $this->error('No payload stored for this row. Replay requires DASHED_WEBHOOK_STORE_PAYLOAD=true on the original webhook.');
            return self::FAILURE;
        }

        $payload = json_decode($row->payload, true);
        if (! is_array($payload)) {
            $this->error('Stored payload is not valid JSON; cannot replay automatically. Manual retry needed.');
            return self::FAILURE;
        }

        // Reset the row so the middleware doesn't short-circuit the replay.
        $row->forceFill([
            'status' => WebhookLog::STATUS_RECEIVED,
            'processed_at' => null,
            'error' => null,
        ])->save();

        $url = URL::route('dashed.frontend.checkout.exchange.post');
        $this->info("Replaying {$provider}/{$eventId} → {$url}");

        $response = Http::asForm()->post($url, $payload);

        if ($response->successful()) {
            $this->info("Replay succeeded (HTTP {$response->status()}).");
            return self::SUCCESS;
        }

        $this->error("Replay failed (HTTP {$response->status()}): {$response->body()}");
        return self::FAILURE;
    }
}
