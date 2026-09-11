<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Rules\SafeUploadedFile;

/**
 * Twee dingen aan de tijdelijke Livewire-uploads, afgedwongen vanuit het
 * pakket zodat geen klantproject ze kan vergeten.
 *
 * De tijdelijke disk is nooit een web-bereikbare disk. Livewire valt zonder
 * instelling terug op de standaarddisk, en `public` wordt via /storage
 * uitgeserveerd; een bestand dat nog door geen enkel component is
 * gevalideerd stond dan vijf minuten lang open op een raadbare URL. Daarom
 * gaat een lege of publiek uitgeserveerde disk naar `local`
 * (storage/app/livewire-tmp). Een project dat bewust een andere prive disk
 * kiest (S3) houdt die.
 *
 * En op de uploadroute hangt SafeUploadedFile. Eigen regels van een project
 * blijven staan; de wachter komt er alleen bij.
 */
class UploadSecurity
{
    public const HTACCESS = <<<'HTACCESS'
    # Geuploade bestanden mogen nooit als script draaien (Apache). Voor nginx: zie CLAUDE.md van dashed-core.
    <IfModule mod_php.c>
        php_flag engine off
    </IfModule>
    <FilesMatch "\.(php|phtml|php[0-9]|phar|pl|py|cgi|sh)$">
        Require all denied
    </FilesMatch>
    Options -ExecCGI -Indexes
    HTACCESS;

    public static function apply(): void
    {
        if (! static::temporaryDiskIsPrivate()) {
            config(['livewire.temporary_file_upload.disk' => 'local']);
        }

        config(['livewire.temporary_file_upload.rules' => static::rulesWithGuard(config('livewire.temporary_file_upload.rules'))]);
    }

    /**
     * Zegt of de ingestelde tijdelijke disk buiten bereik van de webserver
     * ligt. Geen disk is de standaarddisk, en die is in de projecten `public`
     * of `dashed`; een lokale disk met een `url` wordt uitgeserveerd.
     */
    public static function temporaryDiskIsPrivate(): bool
    {
        $name = config('livewire.temporary_file_upload.disk');

        if (! $name) {
            return false;
        }

        $disk = (array) config('filesystems.disks.' . $name, []);

        if (($disk['driver'] ?? null) === 'local' && ! empty($disk['url'])) {
            return false;
        }

        return $disk !== [];
    }

    public static function guardIsActive(): bool
    {
        return collect(static::normalizeRules(config('livewire.temporary_file_upload.rules')))
            ->contains(fn ($rule) => $rule instanceof SafeUploadedFile);
    }

    /**
     * @return array<int, mixed>
     */
    public static function rulesWithGuard(mixed $rules): array
    {
        $rules = static::normalizeRules($rules);

        if (collect($rules)->contains(fn ($rule) => $rule instanceof SafeUploadedFile)) {
            return $rules;
        }

        $rules[] = new SafeUploadedFile();

        return $rules;
    }

    /**
     * Dezelfde uitleg van de instelling als Livewire zelf: null is de
     * standaard, een string is een pipe-lijst.
     *
     * @return array<int, mixed>
     */
    protected static function normalizeRules(mixed $rules): array
    {
        if (is_null($rules)) {
            return ['required', 'file', 'max:12288'];
        }

        if (is_string($rules)) {
            return explode('|', $rules);
        }

        return array_values((array) $rules);
    }

    /**
     * Ruimt de oude, web-bereikbare tijdelijke map op en zet een .htaccess
     * in storage/app/public dat scriptuitvoering uitschakelt. Een .htaccess
     * dat het project zelf al heeft blijft staan.
     */
    public static function hardenPublicStorage(): void
    {
        $publicRoot = storage_path('app/public');
        $oldTemporaryDirectory = $publicRoot . '/livewire-tmp';

        if (is_dir($oldTemporaryDirectory)) {
            \Illuminate\Support\Facades\File::deleteDirectory($oldTemporaryDirectory);
        }

        if (! is_dir($publicRoot)) {
            return;
        }

        $htaccess = $publicRoot . '/.htaccess';

        if (! file_exists($htaccess)) {
            file_put_contents($htaccess, static::HTACCESS . "\n");
        }
    }
}
