<?php

namespace Dashed\DashedCore\Policies;

class PageTemplatePolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'PageTemplate';
    }
}
