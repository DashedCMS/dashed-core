<?php

namespace Dashed\DashedCore\Policies;

class GlobalBlockPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'GlobalBlock';
    }
}
