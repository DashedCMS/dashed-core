<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\File;
use Dashed\DashedCore\Models\Customsetting;

class GenerateFaviconsCommand extends Command
{
    protected $signature = 'dashed:generate-favicons {--site= : Only generate for this site id}';

    protected $description = 'Generate static favicon files for each site, sourced from the site favicon';

    protected const SIZES = [16, 32, 57, 60, 72, 76, 96, 114, 120, 128, 144, 152, 180, 192];

    public function handle(): int
    {
        $targetSite = $this->option('site');
        $sites = $targetSite ? [['id' => (int) $targetSite]] : Sites::getSites();

        foreach ($sites as $site) {
            $siteId = $site['id'] ?? null;
            if (! $siteId) {
                continue;
            }

            $faviconId = Customsetting::get('site_favicon', $siteId);
            if (! $faviconId) {
                $this->warn("Site {$siteId}: no favicon set, skipping");

                continue;
            }

            $media = mediaHelper()->getSingleMedia($faviconId);
            if (! $media) {
                $this->warn("Site {$siteId}: favicon media not found");

                continue;
            }

            $sourcePath = null;
            if (method_exists($media, 'getPath')) {
                $sourcePath = $media->getPath();
            }
            if ((! $sourcePath || ! file_exists($sourcePath)) && method_exists($media, 'getFullUrl')) {
                $url = $media->getFullUrl();
                if ($url && str_starts_with($url, 'http')) {
                    $tmp = tempnam(sys_get_temp_dir(), 'favicon-');
                    @file_put_contents($tmp, @file_get_contents($url));
                    if (file_exists($tmp) && filesize($tmp) > 0) {
                        $sourcePath = $tmp;
                    }
                }
            }

            if (! $sourcePath || ! file_exists($sourcePath)) {
                $this->warn("Site {$siteId}: favicon file missing on disk");

                continue;
            }

            $dir = public_path("favicons/{$siteId}");
            File::ensureDirectoryExists($dir);

            $count = 0;
            foreach (self::SIZES as $size) {
                $out = "{$dir}/favicon-{$size}.png";
                $this->resizePng($sourcePath, $out, $size);
                $count++;
            }

            Customsetting::set('favicon_manifest_generated_at', now()->toDateTimeString(), $siteId);
            $this->info("Site {$siteId}: generated {$count} favicon sizes in {$dir}");
        }

        return self::SUCCESS;
    }

    protected function resizePng(string $source, string $destination, int $size): void
    {
        if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
            \Intervention\Image\Laravel\Facades\Image::read($source)->resize($size, $size)->toPng()->save($destination);

            return;
        }
        if (class_exists(\Intervention\Image\ImageManagerStatic::class)) {
            \Intervention\Image\ImageManagerStatic::make($source)->fit($size, $size)->save($destination);

            return;
        }
        $src = @imagecreatefromstring(file_get_contents($source));
        if (! $src) {
            return;
        }
        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
        imagepng($dst, $destination);
        imagedestroy($src);
        imagedestroy($dst);
    }
}
