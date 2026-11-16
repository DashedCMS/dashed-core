<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sitemap;
use Dashed\DashedCore\Models\BackgroundTask;

class CreateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:create-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sitemap';

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
     * @return mixed
     */
    public function handle()
    {
        // In het takenicoon van de superadmins, zonder melding in de bel: dit
        // draait elke nacht en heeft geen starter.
        $task = BackgroundTask::begin('sitemap', __('Sitemap genereren'), notify: false);

        try {
            Sitemap::create();
        } catch (\Throwable $e) {
            $task->fail($e);

            throw $e;
        }

        $task->finish();
    }
}
