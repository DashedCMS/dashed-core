<?php

namespace Dashed\DashedCore\Commands;

use Dashed\DashedCore\Jobs\CreateAltTextsForAllMediaItems;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Helpers\Http\UrlBuilderTrait;

class AutomaticlyCreateAltTextsForAllMediaItems extends Command
{
    use UrlBuilderTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:create-alt-texts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically create alt texts for all media items using OpenAI';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        CreateAltTextsForAllMediaItems::dispatch();
    }
}
