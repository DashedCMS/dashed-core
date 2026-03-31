<?php

namespace Dashed\DashedCore\Policies;

class RedirectPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Redirect';
    }
}
