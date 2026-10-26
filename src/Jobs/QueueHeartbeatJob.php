<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\SystemHealth;

/**
 * Hartslag van de wachtrij: de scheduler zet deze job elke vijf minuten klaar,
 * en bij uitvoering legt hij vast hoe lang hij moest wachten. Zie SystemHealth.
 */
class QueueHeartbeatJob extends BaseJob
{
    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public readonly int $dispatchedAt)
    {
    }

    public function handle(): void
    {
        SystemHealth::beatQueue($this->dispatchedAt);
    }
}
