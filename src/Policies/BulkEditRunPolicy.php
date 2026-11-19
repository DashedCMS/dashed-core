<?php

namespace Dashed\DashedCore\Policies;

class BulkEditRunPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'BulkEditRun';
    }
}
