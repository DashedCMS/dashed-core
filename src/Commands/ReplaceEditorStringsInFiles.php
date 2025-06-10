<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReplaceEditorStringsInFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:replace-editor-strings-in-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace TiptapEditor and tiptap_converter strings in files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $search = 'TiptapEditor::make(';
        $replace = 'cms()->editorField(';
        $directories = ['app', 'resources'];
        $fileCount = 0;

        foreach ($directories as $dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($dir))
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $path = $file->getRealPath();
                    $content = file_get_contents($path);

                    if (strpos($content, $search) !== false) {
                        $newContent = str_replace($search, $replace, $content);
                        file_put_contents($path, $newContent);
                        $this->line("✔ Replaced in: {$path}");
                        $fileCount++;
                    }
                }
            }
        }

        $this->info("✅ Done! Updated {$fileCount} file(s).");

        $search = 'tiptap_converter()->asHTML(';
        $replace = 'cms()->convertToHtml(';
        $directories = ['app', 'resources'];
        $fileCount = 0;

        foreach ($directories as $dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($dir))
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $path = $file->getRealPath();
                    $content = file_get_contents($path);

                    if (strpos($content, $search) !== false) {
                        $newContent = str_replace($search, $replace, $content);
                        file_put_contents($path, $newContent);
                        $this->line("✔ Replaced in: {$path}");
                        $fileCount++;
                    }
                }
            }
        }

        $this->info("✅ Done! Updated {$fileCount} file(s).");
    }
}
