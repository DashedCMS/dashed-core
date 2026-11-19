<?php

namespace Dashed\DashedCore\Policies;

class AnnouncementBarPolicy extends BaseResourcePolicy
{
    protected function resourceName(): string
    {
        return 'AnnouncementBar';
    }
}
