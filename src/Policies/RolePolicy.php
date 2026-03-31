<?php

namespace Dashed\DashedCore\Policies;

class RolePolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Role';
    }
}
