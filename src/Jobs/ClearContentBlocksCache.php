<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ClearContentBlocksCache implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 60 * 60 * 3;

    public $model;
    public array $blocks;

    public function __construct($model, array $blocks)
    {
        $this->model = $model;
        $this->blocks = $blocks;
    }

    public function handle(): void
    {
        $this->model->clearContentBlockCache($this->blocks);
    }
}
