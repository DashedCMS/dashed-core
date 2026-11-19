<?php

namespace Dashed\DashedCore\Policies;

class ReportPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'Report';
    }
}
