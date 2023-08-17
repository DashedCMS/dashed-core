<?php

namespace Dashed\DashedCore\Classes;

use Flowframe\Drift\Config;
use Flowframe\Drift\Contracts\CachingStrategy;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;

class FilesystemCachingStrategy implements CachingStrategy
{
    public function validate(string $path, string $signature, Config $config): bool
    {
        return Storage::disk('dashed')->exists("__images-cache/{$path}/{$signature}");
    }

    public function resolve(string $path, string $signature, Config $config): string
    {
        return Storage::disk('dashed')->get("__images-cache/{$path}/{$signature}");
    }

    public function cache(string $path, string $signature, string|Image $image, Config $config): void
    {
        Storage::disk('dashed')->put("__images-cache/{$path}/{$signature}", (string) $image);
    }
}
