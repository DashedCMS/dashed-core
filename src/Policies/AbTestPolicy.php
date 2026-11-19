<?php

namespace Dashed\DashedCore\Policies;

class AbTestPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'AbTest';
    }
}
