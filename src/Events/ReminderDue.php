<?php

namespace Dashed\DashedCore\Events;

use Dashed\DashedCore\Models\Reminder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Een herinnering gaat af. dashed-mobile-api luistert op deze klassenaam en
 * stuurt de push naar de app; hernoem hem dus niet zonder dat pakket mee te
 * nemen.
 */
class ReminderDue
{
    use Dispatchable;

    public function __construct(public Reminder $reminder)
    {
    }
}
