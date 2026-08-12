<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Events;

use Dashed\DashedCore\Models\SentEmail;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Een verzonden mail is teruggekomen. De ruwe payload gaat mee, want alleen
 * daarin staat of het een harde of een zachte bounce was, en dat verschil
 * bepaalt of een adres voorgoed geblokkeerd hoort te worden.
 */
class SentEmailBouncedEvent
{
    use Dispatchable;

    /** @param array<string, mixed> $payload */
    public function __construct(public SentEmail $mail, public array $payload)
    {
    }
}
