<?php

namespace Qubiqx\QcommerceCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qcommerce:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Qcommerce in your application';

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
            '--tag' => 'qcommerce-core-config',
            '--force' => 'true'
        ]);
        $this->call('vendor:publish', [
            '--tag' => 'filament-translations',
            '--force' => 'true',
        ]);
        $this->call('migrate', [
            '--force' => 'true',
        ]);
//        $this->call('horizon:install');

        $this->info('QCommerce installed!');
    }
}
