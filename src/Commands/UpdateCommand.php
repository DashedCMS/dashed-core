<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
        $enableMigrations = !$this->option('disable-migrations');

        //        $this->info('Rename all directories from Qcommerce to Dashed...');
        //        File::moveDirectory(base_path('resources/views/qcommerce'), base_path('resources/views/dashed'));
        //        File::moveDirectory(base_path('resources/views/vendor/qcommerce-core'), base_path('resources/views/vendor/dashed-core'));
        //        File::moveDirectory(base_path('resources/views/vendor/qcommerce-ecommerce-core'), base_path('resources/views/vendor/dashed-ecommerce-core'));
        //        File::moveDirectory(storage_path('app/public/qcommerce'), storage_path('app/public/dashed'));
        //        File::moveDirectory(storage_path('app/public/__images-cache/qcommerce'), storage_path('app/public/__images-cache/dashed'));
        //
        //        $this->info('Rename all namespaces in blades from Qcommerce to Dashed...');
        //        $files = File::allFiles(base_path('resources/views'));
        //        foreach ($files as $file) {
        //            $contents = File::get($file);
        //            $contents = str_replace('qcommerce::', 'dashed::', $contents);
        //            $contents = str_replace('qcommerce-', 'dashed-', $contents);
        //            $contents = str_replace('qcommerce', 'dashed', $contents);
        //            $contents = str_replace('Qubiqx\Qcommerce', 'Dashed\Dashed', $contents);
        //            $contents = str_replace('Flowframe\Drift', 'Dashed\Drift', $contents);
        //            File::put($file, $contents);
        //        }
        //
        //        $this->info('Rename all namespaces in classes from Qcommerce to Dashed...');
        //        $files = File::allFiles(base_path('app'));
        //        foreach ($files as $file) {
        //            $contents = File::get($file);
        //            $contents = str_replace('Qubiqx\Qcommerce', 'Dashed\Dashed', $contents);
        //            $contents = str_replace('\Qcommerce', '\Dashed', $contents);
        //            $contents = str_replace('qcommerce', 'dashed', $contents);
        //            File::put($file, $contents);
        //        }
        //
        //        $this->info('Rename all namespaces in routes from Qcommerce to Dashed...');
        //        $files = File::allFiles(base_path('routes'));
        //        foreach ($files as $file) {
        //            $contents = File::get($file);
        //            $contents = str_replace('Qubiqx\Qcommerce', 'Dashed\Dashed', $contents);
        //            $contents = str_replace('\Qcommerce', '\Dashed', $contents);
        //            $contents = str_replace('qcommerce', 'dashed', $contents);
        //            File::put($file, $contents);
        //        }
        //
        //        $this->info('Rename all names from Qcommerce to Dashed...');
        //        $files = File::allFiles(base_path('config'));
        //        foreach ($files as $file) {
        //            $contents = File::get($file);
        //            $contents = str_replace("'qcommerce'", "'dashed'", $contents);
        //            $contents = str_replace("/qcommerce", "/dashed", $contents);
        //            $contents = str_replace("qcommerce", "dashed", $contents);
        //            $contents = str_replace("Qubiqx\Qcommerce", "Dashed\Dashed", $contents);
        //            File::put($file, $contents);
        //        }
        //
        //        $this->info('Retrieving all tables...');
        //        $tables = DB::select('SHOW TABLES');
        //        foreach ($tables as $table) {
        //            $tableName = $table->{'Tables_in_' . env('DB_DATABASE')};
        //            $this->info('Checking table ' . $tableName . '...');
        //            if (str($tableName)->contains('qcommerce')) {
        //                $this->info('Renaming table to ' . $tableName);
        //                \Illuminate\Support\Facades\Schema::rename($tableName, str($tableName)->replace('qcommerce__', 'dashed__'));
        //                $tableName = str($tableName)->replace('qcommerce__', 'dashed__');
        //                $this->info('Table renamed to ' . $tableName);
        //            }
        //
        //            $columns = DB::select('SHOW COLUMNS FROM ' . $tableName);
        //            foreach ($columns as $column) {
        //                $columnName = $column->{'Field'};
        //                $this->info('Checking column ' . $columnName . ' from table ' . $tableName . '...');
        //
        //                try {
        //                    DB::table($tableName)->update([
        //                        $columnName => DB::raw('REPLACE(' . $columnName . ', "qcommerce/", "dashed/")'),
        //                    ]);
        //                } catch (\Exception $e) {
        //                }
        //
        //                try{
        //                    DB::table($tableName)->update([
        //                        $columnName => DB::raw('REPLACE(' . $columnName . ', "Qcommerce", "Dashed")'),
        //                    ]);
        //                }catch (\Exception $e) {
        //                }
        //
        //                try{
        //                    DB::table($tableName)->update([
        //                        $columnName => DB::raw('REPLACE(' . $columnName . ', "Qubiqx", "Dashed")'),
        //                    ]);
        //                }catch (\Exception $e) {
        //                }
        //
        //                try{
        //                    DB::table($tableName)->update([
        //                        $columnName => DB::raw('REPLACE(' . $columnName . ', "qcommerce", "dashed")'),
        //                    ]);
        //                }catch (\Exception $e) {
        //                }
        //            }
        //        }

        $this->info('Default upgrading...');
        //Above is for upgrading from Qcommerce to Dashed
        $this->call('vendor:publish', [
            '--tag' => 'dashed-core-config',
        ]);
        $this->call('vendor:publish', [
            '--tag' => 'dashed-core-assets',
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

        if ($enableMigrations) {
            $this->call('migrate', [
                '--force' => 'true',
            ]);
        }

        $this->info('Dashed updated!');
    }
}
