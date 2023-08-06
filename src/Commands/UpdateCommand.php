<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . env('DB_DATABASE')};
            if (str($tableName)->contains('qcommerce')) {
                \Illuminate\Support\Facades\Schema::rename($tableName, str($tableName)->replace('qcommerce__', 'dashed__'));
                $this->info('Table renamed to ' . str($tableName)->replace('qcommerce__', 'dashed__'));
            }
        }

        File::moveDirectory(base_path('resources/views/qcommerce'), base_path('resources/views/dashed'));
        File::moveDirectory(storage_path('app/public/qcommerce'), storage_path('app/public/dashed'));
        File::moveDirectory(storage_path('app/public/__images-cache/qcommerce'), storage_path('app/public/__images-cache/dashed'));
        dd('asdf');

        //Above is for upgrading from Qcommerce to Dashed
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
