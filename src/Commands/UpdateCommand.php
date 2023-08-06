<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Dashed in your application';

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
        $this->call('vendor:publish', [
            '--tag' => 'dashed-core-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-translations',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'fm-assets',
            '--force' => 'true',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'filament-forms-tinyeditor-assets',
            '--force' => 'true',
        ]);

        $this->call('migrate', [
            '--force' => 'true',
        ]);

        $this->info('Dashed updated!');
    }
}
