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
    protected $signature = 'dashed:update {--disable-migrations}';

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
        $enableMigrations = ! $this->option('disable-migrations');

        $this->info('Default upgrading...');

        foreach (cms()->builder('publishOnUpdate') as $asset) {
            $this->call('vendor:publish', [
                '--tag' => $asset,
            ]);
        }

        $this->call('vendor:publish', [
            '--tag' => 'filament-translations',
        ]);

        if ($enableMigrations) {
            $this->call('migrate', [
                '--force' => 'true',
            ]);
        }

        $this->info('Dashed updated!');
    }
}
