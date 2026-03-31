<?php

namespace Dashed\DashedCore\Policies;

class ReviewPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Review';
    }
}
