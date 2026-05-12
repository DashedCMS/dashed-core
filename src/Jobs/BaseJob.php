<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Jobs\Concerns\HandlesQueueFailures;

/**
 * Conventional starting point for new Dashed-CMS queue jobs. Composes the
 * standard Laravel queue trait stack plus `HandlesQueueFailures`. Existing
 * jobs DO NOT need to extend this — adopt by adding `use HandlesQueueFailures`
 * next to whatever traits they already have.
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use HandlesQueueFailures;

    abstract public function handle(): void;
}
