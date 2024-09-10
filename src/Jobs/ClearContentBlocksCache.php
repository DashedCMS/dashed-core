<?php

namespace Dashed\DashedCore\Jobs;

use Dashed\Seo\Commands\SeoScan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class ClearContentBlocksCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
