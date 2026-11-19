<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

use ZipArchive;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Verzamelt de inzage: per provider een map, per bron een JSON-bestand, en
 * waar zinvol de bestanden zelf (bijlagen uit een chat, uploads uit een
 * formulier). build() schrijft er één ZIP van.
 */
final class PersonalDataExport
{
    private string $prefix = 'algemeen';

    /** @var array<string, string> pad in de zip => inhoud */
    private array $entries = [];

    /** @var array<string, array{disk: string, path: string}> */
    private array $files = [];

    /** @var array<int, array{provider: string, source: string, count: int}> */
    private array $manifest = [];

    public function forProvider(string $key): self
    {
        $this->prefix = Str::slug($key) ?: 'bron';

        return $this;
    }

    /**
     * @param  iterable<mixed>|Arrayable  $rows
     */
    public function add(string $source, iterable|Arrayable $rows): self
    {
        $rows = $rows instanceof Arrayable ? $rows->toArray() : ($rows instanceof Collection ? $rows->toArray() : (is_array($rows) ? $rows : iterator_to_array($rows)));
        $rows = array_map(fn ($row) => $row instanceof Arrayable ? $row->toArray() : (is_object($row) ? (array) $row : $row), $rows);

        if ($rows === []) {
            return $this;
        }

        $path = $this->prefix . '/' . Str::slug($source) . '.json';
        $this->entries[$path] = (string) json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $this->manifest[] = ['provider' => $this->prefix, 'source' => $source, 'count' => count($rows)];

        return $this;
    }

    /**
     * Een bestand van een schijf meenemen. Een bestand dat er niet (meer) is
     * wordt stil overgeslagen: de verwijzing staat al in de JSON.
     */
    public function addFile(string $source, string $disk, string $path, ?string $name = null): self
    {
        $name = $name ?: basename($path);
        $zipPath = $this->prefix . '/' . Str::slug($source) . '/' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

        // Twee bestanden met dezelfde naam mogen elkaar niet overschrijven.
        $i = 1;
        $base = $zipPath;
        while (isset($this->files[$zipPath])) {
            $zipPath = preg_replace('/(\.[^.\/]+)?$/', '-' . (++$i) . '$1', $base, 1);
        }

        $this->files[$zipPath] = ['disk' => $disk, 'path' => $path];

        return $this;
    }

    /** @return array<int, array{provider: string, source: string, count: int}> */
    public function manifest(): array
    {
        return $this->manifest;
    }

    /** @return array<int, string> */
    public function paths(): array
    {
        return array_merge(array_keys($this->entries), array_keys($this->files));
    }

    public function json(string $path): ?string
    {
        return $this->entries[$path] ?? null;
    }

    /**
     * Schrijft de ZIP naar een tijdelijk bestand en geeft het pad terug.
     *
     * @param  array<string, mixed>  $meta
     */
    public function build(array $meta = []): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'avg-export-');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the personal data export archive.');
        }

        $zip->addFromString('manifest.json', (string) json_encode(array_merge($meta, [
            'generated_at' => now()->toIso8601String(),
            'sources' => $this->manifest,
        ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        foreach ($this->entries as $path => $content) {
            $zip->addFromString($path, $content);
        }

        foreach ($this->files as $zipPath => $file) {
            $contents = rescue(fn () => Storage::disk($file['disk'])->exists($file['path']) ? Storage::disk($file['disk'])->get($file['path']) : null, null, report: false);
            if ($contents !== null) {
                $zip->addFromString($zipPath, $contents);
            }
        }

        $zip->close();

        return $tmp;
    }
}
