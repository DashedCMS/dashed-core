<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Dashed\DashedCore\Classes\SecurityAlerts;
use Dashed\DashedCore\Rules\SafeUploadedFile;
use Dashed\DashedCore\Mail\AdminActionAlertMail;

/**
 * Vangnet naast de uploadwachters, standaard ingepland in elk project (zie
 * UploadSecurity::registerSweep()):
 *  1. ruimt storage/app/livewire-tmp op (ouder dan 24 uur); Livewire doet
 *     dat zelf alleen bij een volgende upload;
 *  2. zoekt in de uploadmappen naar PHP-inhoud ("<?php", "<?=") en zet zulke
 *     bestanden in storage/app/upload-quarantine (buiten public/, bewijs
 *     blijft bewaard), met een melding van het type 'Verdachte upload'.
 *
 * Elk uur alleen recent gewijzigde bestanden; 's nachts met --full alles.
 */
class SweepUploadsCommand extends Command
{
    protected $signature = 'dashed:uploads-sweep
        {--dry-run : Alleen melden, niets verwijderen of verplaatsen}
        {--full : Alle bestanden doorzoeken, niet alleen recent gewijzigde}';

    protected $description = 'Ruimt livewire-tmp op en zet bestanden met PHP-inhoud uit de uploadmappen in quarantaine';

    public const QUARANTINE_DIRECTORY = 'upload-quarantine';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $removed = $this->cleanupTemporaryUploads($dryRun);
        $hits = $this->quarantineServerCode($dryRun);

        $this->info("livewire-tmp: {$removed} oude bestanden opgeruimd; " . count($hits) . ' bestand(en) met PHP-inhoud gevonden.');

        if ($hits !== []) {
            $this->report($hits, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string> mappen relatief aan storage/app
     */
    public static function directories(): array
    {
        return array_values(array_unique((array) config('dashed-core.uploads.sweep.directories', ['livewire-tmp', 'public', 'private-uploads'])));
    }

    protected function cleanupTemporaryUploads(bool $dryRun): int
    {
        $directory = storage_path('app/livewire-tmp');
        if (! is_dir($directory)) {
            return 0;
        }

        $threshold = now()->subHours((int) config('dashed-core.uploads.sweep.tmp_max_age_hours', 24))->getTimestamp();
        $removed = 0;

        foreach (File::files($directory, true) as $file) {
            if ($file->getMTime() < $threshold) {
                $dryRun || @unlink($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @return array<int, string> paden relatief aan storage/app
     */
    protected function quarantineServerCode(bool $dryRun): array
    {
        $root = storage_path('app');
        $hits = [];
        $since = $this->option('full')
            ? null
            : now()->subHours((int) config('dashed-core.uploads.sweep.recent_hours', 3))->getTimestamp();

        foreach (static::directories() as $directory) {
            $directory = trim((string) $directory, '/');
            $path = $root . DIRECTORY_SEPARATOR . $directory;

            if ($directory === '' || $directory === static::QUARANTINE_DIRECTORY || ! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path, true) as $file) {
                if ($file->isLink() || ! $file->isFile()) {
                    continue;
                }

                if ($since !== null && $file->getMTime() < $since) {
                    continue;
                }

                // .htaccess die UploadSecurity zelf neerzet is geen upload.
                if ($file->getFilename() === '.htaccess' && $directory === 'public' && $file->getRelativePath() === '') {
                    continue;
                }

                if (! SafeUploadedFile::fileLooksLikeServerCode($file->getPathname())) {
                    continue;
                }

                $relative = $directory . '/' . $file->getRelativePathname();
                $hits[] = $relative;

                if (! $dryRun) {
                    $this->moveToQuarantine($file->getPathname(), $relative);
                }
            }
        }

        return $hits;
    }

    protected function moveToQuarantine(string $absolutePath, string $relative): void
    {
        $target = storage_path('app/' . static::QUARANTINE_DIRECTORY . '/' . now()->format('Ymd-His') . '-' . str_replace('/', '__', $relative) . '.quarantined');

        File::ensureDirectoryExists(dirname($target), 0700);

        if (! @rename($absolutePath, $target)) {
            @unlink($absolutePath);
        }

        @chmod($target, 0600);
    }

    /**
     * @param  array<int, string>  $hits
     */
    protected function report(array $hits, bool $dryRun): void
    {
        Log::critical('[uploads] PHP-inhoud gevonden in uploadmappen', ['files' => $hits, 'dry_run' => $dryRun]);

        if ($dryRun) {
            return;
        }

        $facts = [
            __('Aantal') => (string) count($hits),
            __('Bestanden') => implode("\n", array_slice($hits, 0, 50)),
            __('Actie') => __('Verplaatst naar storage/app/:map (niet publiek)', ['map' => static::QUARANTINE_DIRECTORY]),
            __('Server') => (string) gethostname(),
            __('Tijd') => now()->format('d-m-Y H:i:s'),
        ];

        rescue(fn () => SecurityAlerts::sendCritical(
            SecurityAlerts::TYPE_UPLOAD_THREAT,
            fn () => AdminActionAlertMail::single(__('PHP-inhoud gevonden in uploadmappen'), $facts),
        ), report: true);
    }
}
