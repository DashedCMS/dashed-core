<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Events;

use Dashed\DashedCore\Models\SentEmail;
use Illuminate\Foundation\Events\Dispatchable;

class SentEmailComplainedEvent
{
    use Dispatchable;

    public function __construct(public SentEmail $mail)
    {
    }
}
