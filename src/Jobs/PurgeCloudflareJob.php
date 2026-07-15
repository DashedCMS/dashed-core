<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\DashedCore\Classes\Caching\CloudflarePurger;

class PurgeCloudflareJob extends BaseJob
{
    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public readonly ?string $siteId = null)
    {
    }

    public function handle(): void
    {
        CloudflarePurger::purgeZone($this->siteId);
    }
}
