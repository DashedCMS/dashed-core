<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MigrateToV4 extends Command
{
    protected $signature = 'dashed:migrate-to-v4';
    protected $description = 'Migrate the application to Dashed v4';

    public function handle(): int
    {
        $this->info('🚀 Starting migration to Dashed v4...');

        // 1) Cleanup old config/build files
        $this->cleanupFiles([
            config_path('filament-tiptap-editor.php'),
            base_path('postcss.config.js'),
            base_path('postcss.config.cjs'),
        ]);

        // 2) Update frontend packages via npm
        if (! $this->runNpmInstall()) {
            $this->error('❌ npm install failed.');

            return self::FAILURE;
        }

        // 3) Update composer.json (php ^8.4, laravel/framework ^11.0, dashed/* ^4.0.0)
        $composerChanged = $this->updateComposerJson();
        if ($composerChanged === null) {
            return self::FAILURE; // fatal
        }

        // 4) Comment out Pages\Dashboard::class in AppPanelProvider
        $dashboardChanged = $this->commentDashboardInAppPanelProvider();

        // 5) Update Filament theme assets
        $themeChanged = $this->updateFilamentThemeAssets();

        // 6) Tailwind v4 app.css + config content migratie
        $tailwindChanged = $this->migrateTailwindAppCssAndConfig();

        $viteChanged = $this->updateViteConfig();

        // 7) Run composer update only if something in composer changed (dashboard niet relevant)
        if ($composerChanged) {
            $this->newLine();
            $this->info('🎯 Running: composer update dashed/* laravel/framework');
            if (! $this->runProcess(['composer', 'update', 'dashed/*', 'laravel/framework'])) {
                $this->error('❌ Composer update failed.');

                return self::FAILURE;
            }
        } else {
            $this->warn('No composer.json changes. Skipping composer update.');
        }

        $this->newLine();
        $this->info('🎉 Migration to Dashed v4 completed successfully!');

        return self::SUCCESS;
    }

    // ---------- Helpers ----------

    protected function cleanupFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
                $this->info("🧹 Removed: " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));
            }
        }
    }

    protected function runNpmInstall(): bool
    {
        $this->info('📦 Installing/updating frontend packages (npm)...');

        $command = [
            'npm', 'install',
            '@tailwindcss/aspect-ratio@^0.4.2',
            '@tailwindcss/forms@^0.5.7',
            '@tailwindcss/typography@^0.5.10',
            '@tailwindcss/vite@^4.1.13',
            'aos@^2.3.4',
            'autoprefixer@^10.4.16',
            'axios@^1.6.1',
            'laravel-vite-plugin@^1.0.1',
            'swiper@^11.2.1',
            'tailwindcss@^4.1.13',
            'vite@^5.0.12',
        ];

        return $this->runProcess($command);
    }

    /**
     * @return bool|null true = changed & saved, false = no change, null = fatal error
     */
    protected function updateComposerJson(): ?bool
    {
        $file = base_path('composer.json');

        if (! file_exists($file)) {
            $this->error('composer.json not found!');

            return null;
        }

        $raw = file_get_contents($file);
        $composer = json_decode($raw, true);

        if (! is_array($composer)) {
            $this->error('Invalid composer.json');

            return null;
        }

        $before = json_encode($composer);

        // Ensure sections
        $composer['require'] = $composer['require'] ?? [];

        // Set PHP and Laravel versions
        $composer['require']['php'] = '^8.4';
        $composer['require']['laravel/framework'] = '^11.0';

        // Bump all dashed/* to ^4.0.0 in both require & require-dev
        $updatedDashed = [];
        foreach (['require', 'require-dev'] as $section) {
            if (! isset($composer[$section])) {
                continue;
            }

            foreach ($composer[$section] as $package => $version) {
                if (str_contains($package, 'dashed/')) {
                    $composer[$section][$package] = '^4.0.0';
                    $updatedDashed[$package] = true;
                }
            }
        }

        $after = json_encode($composer);

        if ($before !== $after) {
            file_put_contents(
                $file,
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );

            $this->info('✅ composer.json updated:');
            $this->line('- php => ^8.4');
            $this->line('- laravel/framework => ^11.0');
            if ($updatedDashed) {
                $this->line('- bumped dashed/* => ^4.0.0 for:');
                foreach (array_keys($updatedDashed) as $pkg) {
                    $this->line("   • {$pkg}");
                }
            }

            return true;
        }

        $this->warn('composer.json already up to date. No changes written.');

        return false;
    }

    /**
     * Comments the Pages\Dashboard::class line in AppPanelProvider, if present & not already commented.
     * Returns true if a change was made, false if not.
     */
    protected function commentDashboardInAppPanelProvider(): bool
    {
        $file = app_path('Providers/Filament/AppPanelProvider.php');

        if (! file_exists($file)) {
            $this->warn('AppPanelProvider.php not found, skipping Dashboard comment.');

            return false;
        }

        $contents = file_get_contents($file);

        // If already commented, skip
        if (preg_match('/^\s*\/\/\s*.*Pages\\\\Dashboard::class/m', $contents)) {
            $this->info('Pages\\Dashboard::class already commented. ✅');

            return false;
        }

        // Match a line containing Pages\Dashboard::class optionally with trailing comma, not starting with //
        $pattern = '/^(\s*)(?!\/\/)(.*Pages\\\\Dashboard::class\s*,?\s*)$/m';

        if (! preg_match($pattern, $contents)) {
            $this->warn('No Pages\\Dashboard::class line found in AppPanelProvider. Skipping.');

            return false;
        }

        $updated = preg_replace($pattern, '$1// $2', $contents, 1);
        if ($updated === null) {
            $this->error('Failed to update AppPanelProvider (regex error).');

            return false;
        }

        file_put_contents($file, $updated);
        $this->info('📝 Commented out Pages\\Dashboard::class in AppPanelProvider.');

        return true;
    }

    /**
     * Deletes theme tailwind.config.js and rewrites theme.css with provided content.
     */
    protected function updateFilamentThemeAssets(): bool
    {
        $dir = base_path('resources/css/filament/dashed');
        $changed = false;

        // delete theme-level tailwind.config.js if exists
        $tailwindConfig = $dir . DIRECTORY_SEPARATOR . 'tailwind.config.js';
        if (file_exists($tailwindConfig)) {
            @unlink($tailwindConfig);
            $this->info('🧹 Removed: resources/css/filament/dashed/tailwind.config.js');
            $changed = true;
        } else {
            $this->warn('resources/css/filament/dashed/tailwind.config.js not found (skip delete).');
        }

        // ensure directory exists
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // rewrite theme.css
        $themeCss = $dir . DIRECTORY_SEPARATOR . 'theme.css';
        $content = <<<'CSS'
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../../vendor/ralphjsmit/laravel-filament-media-library/resources/css/index.css';

@source '../../../../app/Filament';
@source '../../../../resources/views/filament';
@source '../../../../app/Filament/**/*.php';
@source '../../../../resources/views/filament/**/*.blade.php';
@source '../../../../vendor/filament/**/*.blade.php';
@source '../../../../vendor/dashed/**/*.blade.php';
@source '../../../../vendor/ralphjsmit/laravel-filament-media-library/resources/**/*.blade.php';
CSS;

        $result = file_put_contents($themeCss, $content . "\n");
        if ($result === false) {
            $this->error('❌ Failed to write resources/css/filament/dashed/theme.css');
        } else {
            $this->info('📝 Rewrote: resources/css/filament/dashed/theme.css');
            $changed = true;
        }

        return $changed;
    }

    /**
     * - Vervangt 3 imports in resources/css/app.css door enkele @import "tailwindcss";
     * - Leest content-globs uit tailwind.config.(js|cjs) en schrijft ze als @source;
     * - Verwijdert de content property uit de config.
     */
    protected function migrateTailwindAppCssAndConfig(): bool
    {
        return true;
        $configPath = $this->findTailwindConfig();
        $globs = [];

        if ($configPath && file_exists($configPath)) {
            $this->info('🔎 Using Tailwind config: ' . basename($configPath));
            $raw = file_get_contents($configPath);

            // Extract globs from content: [ ... ]
            $globs = $this->extractContentGlobs($raw);
            $unique = [];
            foreach ($globs as $g) {
                $unique[$g] = true;
            }
            $globs = array_keys($unique);

            // Remove `content: [...]` from config
            $stripped = $this->stripContentFromTailwindConfig($raw);

            if ($stripped !== $raw) {
                // backup
                @copy($configPath, $configPath . '.bak');
                if (file_put_contents($configPath, $stripped) !== false) {
                    $this->info('🧽 Removed `content` from ' . basename($configPath) . ' (backup created).');
                } else {
                    $this->error('❌ Failed to write updated ' . basename($configPath));
                }
            } else {
                $this->warn('No `content` property found to remove in ' . basename($configPath));
            }
        } else {
            $this->warn('Tailwind config not found (tailwind.config.js/cjs). Skipping content extraction.');
        }

        // Now update app.css
        return $this->updateAppCssImportsAndSources($globs);
    }

    protected function findTailwindConfig(): ?string
    {
        $candidates = [
            base_path('tailwind.config.js'),
            base_path('tailwind.config.cjs'),
        ];

        foreach ($candidates as $c) {
            if (file_exists($c)) {
                return $c;
            }
        }

        return null;
    }

    protected function extractContentGlobs(string $js): array
    {
        // strip comments
        $clean = preg_replace('#//.*$#m', '', $js);
        $clean = preg_replace('#/\*.*?\*/#s', '', $clean);

        // find content: [ ... ]
        if (! preg_match('/content\s*:\s*\[([\s\S]*?)\]/', $clean, $m)) {
            return [];
        }
        $inside = $m[1];

        // find string literals within the array (single/double/backtick)
        preg_match_all('/(["\'`])((?:\\\\.|(?!\1).)*)\1/', $inside, $mm);
        $globs = [];
        foreach ($mm[2] as $s) {
            // unescape basic sequences
            $globs[] = stripcslashes($s);
        }

        return $globs;
    }

    protected function stripContentFromTailwindConfig(string $js): string
    {
        // Remove comments to make commas logic simpler (but operate on original positions risky),
        // Instead do a tolerant regex that removes `content: [ ... ]` with optional trailing comma.
        $pattern = '/(\s*content\s*:\s*\[[\s\S]*?\]\s*,?)/';

        return preg_replace($pattern, '', $js, 1) ?? $js;
    }

    protected function updateAppCssImportsAndSources(array $globs): bool
    {
        return true;
        $path = base_path('resources/css/app.css');
        $changed = false;

        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        $existing = file_exists($path) ? file_get_contents($path) : '';

        // Remove legacy triple imports
        $patterns = [
            "/^\s*@import\s+['\"]tailwindcss\/base['\"];\s*$/m",
            "/^\s*@import\s+['\"]tailwindcss\/components['\"];\s*$/m",
            "/^\s*@import\s+['\"]tailwindcss\/utilities['\"];\s*$/m",
        ];
        $updated = preg_replace($patterns, '', $existing);

        // Ensure single @import "tailwindcss";
        if (! preg_match('/@import\s+["\']tailwindcss["\'];/', $updated)) {
            $updated = "@import \"tailwindcss\";\n\n" . ltrim($updated);
            $changed = true;
        } elseif ($updated !== $existing) {
            $changed = true;
        }

        // Collect existing @source lines, then merge with globs
        preg_match_all('/@source\s+["\']([^"\']+)["\'];/', $updated, $m);
        $existingSources = $m[1] ?? [];

        $merged = [];
        foreach (array_merge($existingSources, $globs) as $g) {
            $g = trim($g);
            if ($g !== '') {
                $merged[$g] = true;
            }
        }
        $sources = array_keys($merged);

        // Remove all existing @source lines
        $updated = preg_replace('/^\s*@source\s+["\'][^"\']+["\'];\s*$/m', '', $updated);

        // Insert sources block right after the @import line
        $lines = ["@import \"tailwindcss\";"];
        foreach ($sources as $g) {
            $lines[] = "@source '" . $g . "';";
        }
        $newHeader = implode("\n", $lines) . "\n\n";

        // Replace first @import with header
        $updated = preg_replace('/@import\s+["\']tailwindcss["\'];/', $newHeader, $updated, 1);

        if ($updated !== $existing) {
            // backup
            if (file_exists($path)) {
                @copy($path, $path . '.' . date('Ymd-His') . '.bak');
            }
            if (file_put_contents($path, $updated) !== false) {
                $this->info('📝 Updated resources/css/app.css (single import + @source from config).');
                $changed = true;
            } else {
                $this->error('❌ Failed to write resources/css/app.css');
            }
        } else {
            $this->warn('resources/css/app.css already up to date.');
        }

        return $changed;
    }

    protected function runProcess(array $command): bool
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        return $process->isSuccessful();
    }

    protected function updateViteConfig(): bool
    {
        // Zoek bestaande config; val anders terug op .js
        $candidates = [
            base_path('vite.config.ts'),
            base_path('vite.config.js'),
        ];

        $target = null;
        foreach ($candidates as $c) {
            if (file_exists($c)) {
                $target = $c;
                break;
            }
        }
        if (! $target) {
            $target = base_path('vite.config.js');
        }

        // Gewenste content (exact jouw snippet)
        $desired = <<<'JS'
import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/dashed/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
JS;

        // Normaliseer line endings voor correcte vergelijking
        $normalize = fn (string $s) => trim(str_replace(["\r\n", "\r"], "\n", $s));
        $current = file_exists($target) ? $normalize(file_get_contents($target)) : '';
        $shouldWrite = $current !== $normalize($desired);

        if (! $shouldWrite) {
            $this->warn(basename($target) . ' is al up-to-date. Skipping.');
            return false;
        }

        // Backup en schrijf
        if (file_exists($target)) {
            @copy($target, $target . '.bak');
        }
        if (file_put_contents($target, $desired . "\n") === false) {
            $this->error('❌ Kon ' . basename($target) . ' niet schrijven.');
            return false;
        }

        $this->info('📝 Rewrote: ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $target));
        return true;
    }

}
