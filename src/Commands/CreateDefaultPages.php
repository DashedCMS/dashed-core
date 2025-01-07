<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

class CreateDefaultPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:create-default-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default pages for DashedCMS';

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
        $this->info('Creating default pages for DashedCMS');

        foreach (collect(cms()->builder('createDefaultPages'))->sortKeysDesc()->toArray() as $class => $method) {
            if (is_array($method)) {
                foreach ($method as $m) {
                    $class::$m();
                }
            } else {
                $class::$method();
            }
        }

        $this->info('Default pages created');
    }
}
