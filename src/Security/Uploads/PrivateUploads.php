<?php

namespace Dashed\DashedCore\Security\Uploads;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Eén plek voor bestanden van bezoekers (formulieren, product-extra's,
 * livechat-bijlagen): nooit publiek, altijd een servergegenereerde naam, en
 * alleen op te halen via de route dashed.private-upload.
 *
 * Opslag: de DigitalOcean/S3-disk van de site als die er is (disk "dashed",
 * of "spaces" als de media lokaal staan), als privé object; anders de lokale
 * disk "local" (storage/app, buiten public/). Te overschrijven met
 * dashed-core.uploads.private_disk.
 *
 * Een opgeslagen bestand heet "private-uploads/{map}/{uuid}.{ext}"; aan dat
 * voorvoegsel herkennen formulieren, mails en het beheer dat de link via de
 * beveiligde route moet.
 */
class PrivateUploads
{
    public const PREFIX = 'private-uploads/';

    public const ROUTE = 'dashed.private-upload';

    /** Extensie (uit de inhoud) => content-type bij het serveren. */
    public const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'avif' => 'image/avif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        'txt' => 'text/plain',
    ];

    /** Mag de browser tonen; de rest wordt altijd als download geserveerd. */
    public const INLINE_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'pdf'];

    /**
     * Extra types die moveIntoPrivate() alleen voor bestaande bestanden
     * meeneemt (oude uploads van vóór de typelijst, bijv. AML-video's), met
     * de extensie waaronder ze privé komen. Nieuwe uploads (store()) blijven
     * op CONTENT_TYPES; deze worden altijd als download geserveerd.
     *
     * @var array<string, string>
     */
    public const LEGACY_MOVE_TYPES = [
        'jfif' => 'jpg', 'jpe' => 'jpg', 'pjpeg' => 'jpg',
        'tif' => 'tif', 'tiff' => 'tiff', 'bmp' => 'bmp',
        'mp4' => 'mp4', 'm4v' => 'm4v', 'mov' => 'mov', '3gp' => '3gp', 'webm' => 'webm',
    ];

    public static function diskName(): string
    {
        if ($configured = config('dashed-core.uploads.private_disk')) {
            return (string) $configured;
        }

        foreach (['dashed', 'spaces'] as $candidate) {
            $driver = (string) config("filesystems.disks.{$candidate}.driver", '');
            if (str_starts_with($driver, 's3')) {
                return $candidate;
            }
        }

        return 'local';
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    /**
     * Slaat een upload privé op onder een gegenereerde naam. De extensie komt
     * uit de inhoud, nooit uit de naam van de bezoeker. Null als het type niet
     * mag of opslaan mislukt.
     *
     * @param  array<int, string>|null  $allowedExtensions  null = alles uit CONTENT_TYPES
     */
    public static function store(UploadedFile $file, string $directory, ?array $allowedExtensions = null): ?string
    {
        $extension = static::extensionFor($file);
        $allowed = array_map('strtolower', $allowedExtensions ?? array_keys(static::CONTENT_TYPES));

        if ($extension === null || ! in_array($extension, $allowed, true)) {
            return null;
        }

        $directory = static::PREFIX . trim(Str::slug($directory) ?: 'files', '/');
        $name = Str::uuid() . '.' . $extension;

        $stream = fopen($file->getRealPath(), 'rb');
        if (! $stream) {
            return null;
        }

        try {
            $ok = static::disk()->writeStream($directory . '/' . $name, $stream, ['visibility' => 'private']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $ok ? $directory . '/' . $name : null;
    }

    /**
     * Zelfde als store(), maar vanaf een pad op schijf (bv. bij het verhuizen
     * van oude publieke bestanden).
     */
    public static function storeFromPath(string $absolutePath, string $directory, ?string $originalName = null): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        return static::store(new UploadedFile($absolutePath, $originalName ?? basename($absolutePath), null, null, true), $directory);
    }

    /**
     * Verhuist een bestaand (publiek) bestand naar de privé opslag: kopiëren,
     * controleren dat de kopie even groot is, en pas dan het origineel
     * verwijderen. Null als het bron-bestand ontbreekt, het type niet mag of
     * de kopie niet klopt; het origineel blijft dan staan.
     */
    public static function moveIntoPrivate(string $sourceDisk, string $sourcePath, string $directory, bool $deleteSource = true): ?string
    {
        $source = Storage::disk($sourceDisk);

        if (static::isPrivate($sourcePath) || ! $source->exists($sourcePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $extension = static::LEGACY_MOVE_TYPES[$extension] ?? $extension;
        if (! array_key_exists($extension, static::CONTENT_TYPES) && ! in_array($extension, static::LEGACY_MOVE_TYPES, true)) {
            return null;
        }

        $target = static::PREFIX . trim(Str::slug($directory) ?: 'files', '/') . '/' . Str::uuid() . '.' . $extension;
        $size = (int) $source->size($sourcePath);

        $stream = $source->readStream($sourcePath);
        if (! $stream) {
            return null;
        }

        try {
            $ok = static::disk()->writeStream($target, $stream, ['visibility' => 'private']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $ok || ! static::disk()->exists($target) || (int) static::disk()->size($target) !== $size) {
            rescue(fn () => static::disk()->delete($target), null, false);

            return null;
        }

        if ($deleteSource) {
            rescue(fn () => $source->delete($sourcePath), null, false);
        }

        return $target;
    }

    /**
     * Verhuizen van bestaande bestanden bij een migratie: standaard alleen in
     * productie. Lokaal draaien migraties vaak op een kopie van de
     * productiedatabase met de echte DigitalOcean-disk erachter.
     */
    public static function privatizeOnMigrate(): bool
    {
        return (bool) config('dashed-core.uploads.privatize_existing_on_migrate', app()->isProduction());
    }

    public static function extensionFor(UploadedFile $file): ?string
    {
        $extension = strtolower((string) $file->guessExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        // finfo kent heic/avif/csv niet altijd; dan telt de naam, mits die op de lijst staat.
        if ($extension === '' || $extension === 'bin' || $extension === 'txt') {
            $client = strtolower((string) $file->getClientOriginalExtension());
            if (in_array($client, ['heic', 'heif', 'avif', 'csv', 'txt'], true)) {
                $extension = $client;
            }
        }

        return array_key_exists($extension, static::CONTENT_TYPES) ? $extension : null;
    }

    public static function isPrivate(mixed $reference): bool
    {
        return is_string($reference) && preg_match(static::pattern(), $reference) === 1;
    }

    public static function pattern(): string
    {
        return '#^' . preg_quote(static::PREFIX, '#') . '[a-z0-9-]+/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-z0-9]{2,5}$#';
    }

    /**
     * Link voor het beheer: werkt alleen voor ingelogde paneelgebruikers.
     */
    public static function url(string $reference): string
    {
        return URL::route(static::ROUTE, ['path' => static::routePath($reference)]);
    }

    /**
     * Tijdelijke, gesigneerde link die ook zonder login werkt (de bezoeker
     * die zijn eigen chatbijlage ziet, een link in een mail).
     */
    public static function temporaryUrl(string $reference, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(static::ROUTE, now()->addMinutes($minutes), ['path' => static::routePath($reference)]);
    }

    /**
     * Voor waarden die privé óf nog een oud publiek pad kunnen zijn.
     */
    public static function urlFor(mixed $value, string $legacyDisk = 'dashed'): string
    {
        if (static::isPrivate($value)) {
            return static::url($value);
        }

        return (string) rescue(fn () => Storage::disk($legacyDisk)->url((string) $value), '', false);
    }

    public static function routePath(string $reference): string
    {
        return substr($reference, strlen(static::PREFIX));
    }

    public static function extension(string $reference): string
    {
        return strtolower(pathinfo($reference, PATHINFO_EXTENSION));
    }

    public static function isImage(string $reference): bool
    {
        return str_starts_with(static::CONTENT_TYPES[static::extension($reference)] ?? '', 'image/');
    }

    public static function delete(string $reference): void
    {
        if (static::isPrivate($reference)) {
            rescue(fn () => static::disk()->delete($reference), null, false);
        }
    }
}
