<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sitemap;
use Illuminate\Support\Facades\Storage;

class MigrateStorageDataToSpace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:migrate-to-space';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate storage data to space';

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
        $folders = ['dashed'];

        foreach ($folders as $folder) {
            $files = Storage::disk('public')->allFiles($folder);
//            dd($files);

            $this->withProgressBar($files, function ($filePath) {
                if (!Storage::disk('dashed')->exists($filePath)) {
                    //            foreach ($files as $filePath) {
                    $this->newLine();
                    $this->info('Downloading file: ' . $filePath);
                    $file = Storage::disk('public')->get($filePath);
                    Storage::disk('dashed')->put($filePath, $file);
                }
            });
        }

        return Command::SUCCESS;
    }
}
