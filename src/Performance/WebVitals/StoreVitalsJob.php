<?php

namespace Dashed\DashedCore\Performance\WebVitals;

use Illuminate\Bus\Queueable;
use Dashed\DashedCore\Models\WebVital;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class StoreVitalsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public array $payload)
    {
    }

    public function handle(): void
    {
        WebVital::create([
            'site_id' => $this->payload['site_id'] ?? null,
            'metric' => $this->payload['metric'],
            'value' => $this->payload['value'],
            'rating' => $this->payload['rating'] ?? null,
            'url' => $this->payload['url'],
            'device' => $this->payload['device'],
            'created_at' => now(),
        ]);
    }
}
