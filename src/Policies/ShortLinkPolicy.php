<?php

namespace Dashed\DashedCore\Policies;

class ShortLinkPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'ShortLink';
    }
}
