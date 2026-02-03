<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Helpers\Http\UrlBuilderTrait;

class SyncGoogleReviews extends Command
{
    use UrlBuilderTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:sync-google-reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Reviews';

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
        if (Customsetting::get('google_maps_places_key') && Customsetting::get('google_maps_places_id')) {
            \Dashed\DashedCore\Jobs\SyncGoogleReviews::dispatch();
        }
    }
}
