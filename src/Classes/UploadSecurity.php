<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Validator;
use Dashed\DashedCore\Rules\SafeUploadedFile;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

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

    public const SVG_BLOCK_BEGIN = '# BEGIN dashed-svg';

    public const SVG_BLOCK_END = '# END dashed-svg';

    /**
     * Een svg die rechtstreeks vanaf een lokale publieke schijf geopend
     * wordt draait op het domein van de site zelf. Met deze header voert de
     * browser er geen script in uit, wat er ook in het bestand staat. Het
     * blok is gemarkeerd: alleen wat tussen de markeringen staat wordt bij
     * een update vervangen, de rest van het bestand is van het project.
     */
    public const SVG_BLOCK = <<<'HTACCESS'
    # BEGIN dashed-svg
    # Beheerd door dashed-core: wat tussen BEGIN en END staat wordt bij een update vervangen.
    # Een direct geopende svg mag geen script uitvoeren.
    <IfModule mod_headers.c>
        <FilesMatch "(?i)\.(svg|svgz)$">
            Header always set Content-Security-Policy "script-src 'none'; sandbox"
        </FilesMatch>
    </IfModule>
    # END dashed-svg
    HTACCESS;

    /**
     * De wachter staat als regelnaam in de config en niet als object: config:cache
     * schrijft de config met var_export weg, en een object daarin breekt dat.
     */
    public const RULE = 'safe_uploaded_file';

    public static function apply(): void
    {
        Validator::extend(static::RULE, function (string $attribute, mixed $value, array $parameters, ValidatorContract $validator): bool {
            $message = null;
            (new SafeUploadedFile())->validate($attribute, $value, function (string $reason) use (&$message): void {
                $message = $reason;
            });

            if ($message !== null) {
                $validator->setCustomMessages([$attribute . '.' . static::RULE => $message]);
            }

            return $message === null;
        }, __('De inhoud van dit bestand is niet toegestaan.'));

        if (! static::temporaryDiskIsPrivate()) {
            config(['livewire.temporary_file_upload.disk' => 'local']);
        }

        config(['livewire.temporary_file_upload.rules' => static::rulesWithGuard(config('livewire.temporary_file_upload.rules'))]);

        static::silenceStartUploadProbes();
    }

    /**
     * Scanners pakken de eerste Livewire-snapshot op een publieke pagina (vaak
     * `notifications`) en roepen daar `_startUpload` op aan, op zoek naar een
     * plek om een bestand neer te zetten. Livewire weigert dat al met
     * MissingFileUploadsTraitException; zonder deze mapping werd elke poging
     * een 500 en een Flare-melding (6000 op dashed.nl). Geen echte browser
     * doet dit: onze uploads gaan altijd naar het eigen component. Laravel
     * mapt vóór het rapporteren, en een HttpException wordt nooit
     * gerapporteerd, dus dit ene punt dekt zowel de melding als de 500.
     */
    protected static function silenceStartUploadProbes(): void
    {
        if (! class_exists(\Livewire\Features\SupportFileUploads\MissingFileUploadsTraitException::class)) {
            return;
        }

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        if (! method_exists($handler, 'map')) {
            return;
        }

        $handler->map(
            \Livewire\Features\SupportFileUploads\MissingFileUploadsTraitException::class,
            fn () => new \Symfony\Component\HttpKernel\Exception\HttpException(400, 'Bad Request'),
        );
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
            ->contains(fn ($rule) => static::isGuard($rule));
    }

    /**
     * @return array<int, mixed>
     */
    public static function rulesWithGuard(mixed $rules): array
    {
        $rules = static::normalizeRules($rules);

        if (collect($rules)->contains(fn ($rule) => static::isGuard($rule))) {
            return $rules;
        }

        $rules[] = static::RULE;

        return $rules;
    }

    /**
     * Zowel de regelnaam als een los object van een project telt als wachter.
     */
    public static function isGuard(mixed $rule): bool
    {
        if ($rule instanceof SafeUploadedFile) {
            return true;
        }

        return is_string($rule) && (explode(':', $rule, 2)[0] === static::RULE);
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
        $publicRoot = static::publicStorageRoot();
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
            // Een bestand dat we zelf net neerzetten krijgt de svg-regel
            // meteen mee; een bestaand bestand blijft hier ongemoeid.
            static::ensureSvgHeaders($publicRoot);
        }
    }

    /**
     * Zet het gemarkeerde svg-blok in het .htaccess van een publieke
     * opslagmap, of werkt het bij als het er al staat. Alles buiten de
     * markeringen blijft byte voor byte staan: een .htaccess dat een
     * beheerder zelf heeft aangepast wordt aangevuld, nooit overschreven.
     * Zonder $root: storage/app/public en de map van elke lokale schijf met
     * een url.
     *
     * @return array<string, string> per map: added, updated, present, created of unwritable
     */
    public static function ensureSvgHeaders(?string $root = null): array
    {
        $results = [];

        foreach ($root !== null ? [$root] : static::publicRoots() as $directory) {
            $directory = rtrim($directory, '/');

            if (! is_dir($directory)) {
                continue;
            }

            $htaccess = $directory . '/.htaccess';

            if (! file_exists($htaccess)) {
                $results[$directory] = static::writeAtomically($htaccess, static::HTACCESS . "\n\n" . static::SVG_BLOCK . "\n") ? 'created' : 'unwritable';

                continue;
            }

            $current = @file_get_contents($htaccess);

            if ($current === false) {
                $results[$directory] = 'unwritable';

                continue;
            }

            // Het regeleinde van het bestand zelf: een bestand dat met CRLF bewaard is blijft CRLF.
            $crlf = substr_count($current, "\r\n");
            $eol = $crlf > 0 && $crlf >= substr_count($current, "\n") - $crlf ? "\r\n" : "\n";
            $block = str_replace("\n", $eol, static::SVG_BLOCK);
            $blocks = static::svgBlocksIn($current);

            if (count($blocks) === 1 && str_replace("\r\n", "\n", $blocks[0][0]) === static::SVG_BLOCK) {
                $results[$directory] = 'present';

                continue;
            }

            if ($blocks === []) {
                $updated = $current . ($current === '' || str_ends_with($current, "\n") ? '' : $eol) . $eol . $block . $eol;
                $status = 'added';
            } else {
                // Van achter naar voren, elk blok afzonderlijk: het eerste
                // wordt het actuele blok, elk volgend verdwijnt met zijn
                // eigen regeleinde. Wat ertussen staat blijft byte voor byte.
                $updated = $current;

                foreach (array_reverse($blocks, true) as $index => [$text, $offset]) {
                    $length = strlen($text);

                    if ($index > 0 && preg_match('/\G\r?\n/', $updated, $newline, 0, $offset + $length)) {
                        $length += strlen($newline[0]);
                    }

                    $updated = substr($updated, 0, $offset) . ($index === 0 ? $block : '') . substr($updated, $offset + $length);
                }

                $status = 'updated';
            }

            // Een bestand dat de beheerder alleen-lezen maakte blijft zoals het is: rename zou het anders toch vervangen.
            $results[$directory] = is_writable($htaccess) && static::writeAtomically($htaccess, $updated) ? $status : 'unwritable';
        }

        return $results;
    }

    /**
     * Elk eigen blok in de tekst, als paar van BEGIN tot de eerstvolgende
     * END, ongeacht het regeleinde. Tussen de twee mag geen tweede BEGIN
     * staan: een losse BEGIN zonder END slokt zo nooit de regels van een
     * beheerder op.
     *
     * @return array<int, array{0: string, 1: int}> tekst en positie
     */
    protected static function svgBlocksIn(string $content): array
    {
        $begin = preg_quote(static::SVG_BLOCK_BEGIN, '/');
        $end = preg_quote(static::SVG_BLOCK_END, '/');
        $pattern = '/^[ \t]*' . $begin . '[ \t]*\r?\n(?:(?![ \t]*' . $begin . ')[^\n]*\n)*?[ \t]*' . $end . '[ \t]*(?=\r?\n|\z)/m';

        return preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) ? $matches[0] : [];
    }

    /**
     * Schrijft via een tijdelijk bestand in dezelfde map en zet het dan op
     * zijn plaats: een webserver leest nooit een half geschreven .htaccess.
     * De tijdelijke naam begint met `.ht`, die Apache standaard niet uitserveert.
     */
    protected static function writeAtomically(string $path, string $content): bool
    {
        $temporary = dirname($path) . '/.htaccess.dashed-tmp-' . bin2hex(random_bytes(6));

        try {
            if (@file_put_contents($temporary, $content, LOCK_EX) !== strlen($content)) {
                @unlink($temporary);

                return false;
            }

            $mode = file_exists($path) ? @fileperms($path) : false;
            @chmod($temporary, $mode !== false ? $mode & 0777 : 0644);

            if (! @rename($temporary, $path)) {
                @unlink($temporary);

                return false;
            }

            clearstatcache(true, $path);

            return true;
        } catch (\Throwable) {
            @unlink($temporary);

            return false;
        }
    }

    /**
     * Of het svg-blok geldt voor deze map: in het .htaccess van de map zelf
     * of van een map erboven (Apache erft), tot en met de opslagmap.
     */
    public static function svgHeadersPresent(string $directory): bool
    {
        $directory = rtrim($directory, '/');
        $stop = dirname(storage_path());

        for ($depth = 0; $depth < 12 && $directory !== '' && $directory !== '/' && $directory !== '.'; $depth++) {
            $content = is_file($directory . '/.htaccess') ? (string) @file_get_contents($directory . '/.htaccess') : '';

            if (str_contains($content, static::SVG_BLOCK_BEGIN) && str_contains($content, 'Content-Security-Policy')) {
                return true;
            }

            if ($directory === $stop || $directory === base_path()) {
                break;
            }

            $directory = dirname($directory);
        }

        return false;
    }

    /**
     * Alleen voor tests: een andere map in plaats van storage/app/public,
     * zodat een test nooit in het echte .htaccess van het project schrijft.
     * In productie blijft dit null.
     */
    public static ?string $publicStorageRootOverride = null;

    /** storage/app/public, zonder slash aan het eind. */
    public static function publicStorageRoot(): string
    {
        return rtrim(static::$publicStorageRootOverride ?? storage_path('app/public'), '/');
    }

    /**
     * De mappen die een webserver rechtstreeks uitserveert: storage/app/public
     * en de map van elke lokale schijf met een url, zonder mappen die al
     * onder een andere uit de lijst vallen.
     *
     * @return array<int, string>
     */
    public static function publicRoots(): array
    {
        $roots = [static::publicStorageRoot()];

        foreach ((array) config('filesystems.disks', []) as $disk) {
            if (is_array($disk) && ($disk['driver'] ?? null) === 'local' && ! empty($disk['url']) && is_string($disk['root'] ?? null) && $disk['root'] !== '') {
                $roots[] = rtrim($disk['root'], '/');
            }
        }

        $roots = array_values(array_unique($roots));

        return array_values(array_filter($roots, function (string $root) use ($roots) {
            foreach ($roots as $other) {
                if ($other !== $root && str_starts_with($root . '/', $other . '/')) {
                    return false;
                }
            }

            return true;
        }));
    }
}
