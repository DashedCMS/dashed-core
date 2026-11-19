<?php

namespace Dashed\DashedCore\Policies;

class ScheduledChangePolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'ScheduledChange';
    }
}
