<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Events;

use Dashed\DashedCore\Models\SentEmail;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Een verzonden mail is afgeleverd. Tegenhanger van SentEmailBouncedEvent en
 * SentEmailComplainedEvent, zodat andere pakketten op een bezorging kunnen
 * reageren zonder dat dashed-core hoeft te weten wie er meeluistert.
 */
class SentEmailDeliveredEvent
{
    use Dispatchable;

    public function __construct(public SentEmail $mail)
    {
    }
}
