<?php

namespace Dashed\DashedCore\Policies;

class NotFoundPagePolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'NotFoundPage';
    }
}
