<?php

namespace Qubiqx\QcommerceCore\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceCore\Classes\Sitemap;
use Qubiqx\QcommerceCore\Jobs\Sitemap\CreateSitemapJob;

class CreateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qcommerce:createsitemap';

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
        CreateSitemapJob::dispatch()->delay(now()->addMinutes(rand(0, 240)));
//        Sitemap::create();
    }
}
