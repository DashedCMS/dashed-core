<?php

namespace Dashed\DashedCore\Policies;

class UserPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'User';
    }
}
