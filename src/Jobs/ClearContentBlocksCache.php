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

    /**
     * Deze job leest de contentblokken van elk route-model door en vergeet de
     * cachesleutels die erbij horen. Dat is een wandeling door wat tabellen, in
     * porties van duizend; drie uur was daar nooit een grens voor maar een
     * uitnodiging. Erger: een timeout boven de retry_after van de wachtrij
     * betekent dat een lopende job opnieuw wordt uitgedeeld, en met tries op 1
     * eindigt dat als "has been attempted too many times".
     */
    public $timeout = 900;

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
