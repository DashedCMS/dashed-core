<?php

namespace Dashed\DashedCore\Rules;

use Closure;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Globale wachter voor elk geüpload bestand, front-end én beheer. Elke
 * Livewire- en Filament-upload komt via /livewire/upload-file binnen vóórdat
 * een component iets valideert; UploadSecurity hangt deze regel aan die
 * route. Server-side code (PHP, Phar, CGI, shellscripts, .htaccess) wordt
 * geweigerd op naam, op gesnoven inhoudstype en op inhoud, ongeacht de
 * extensie die de client meestuurt. Een vangnet naast de allowlists per
 * formulier, geen vervanging ervan.
 */
class SafeUploadedFile implements ValidationRule
{
    /** Extensies die nooit ergens mogen landen, ook niet als tussenstuk (shell.php.jpg). */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'pht', 'phtm', 'phtml', 'phar', 'phpt', 'inc',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'zsh', 'ksh',
        'exe', 'dll', 'com', 'bat', 'cmd', 'msi', 'scr',
        'jsp', 'jspx', 'asp', 'aspx', 'asa', 'asax', 'ashx', 'asmx', 'cer', 'cfm',
        'shtml', 'shtm', 'htaccess', 'htpasswd', 'user.ini',
    ];

    public const BLOCKED_MIME_TYPES = [
        'text/x-php', 'application/x-php', 'application/x-httpd-php', 'application/x-httpd-php-source', 'application/php',
        'text/x-perl', 'application/x-perl', 'text/x-python', 'application/x-python', 'text/x-ruby',
        'text/x-shellscript', 'application/x-sh', 'application/x-shellscript',
        'application/x-msdownload', 'application/x-dosexec', 'application/x-msdos-program', 'application/vnd.microsoft.portable-executable',
    ];

    /**
     * Zodat config:cache een project niet breekt dat dit object zelf in
     * config/livewire.php zet; de regel heeft geen staat.
     */
    public static function __set_state(array $properties): static
    {
        return new static();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('Ongeldig bestand.'));

            return;
        }

        if ($reason = static::rejectionReason($value)) {
            $fail($reason);
        }
    }

    /**
     * De reden waarom een bestand geweigerd wordt, of null als het door mag.
     */
    public static function rejectionReason(UploadedFile $file): ?string
    {
        return static::rejectionReasonFor(
            (string) $file->getClientOriginalName(),
            (string) $file->guessExtension(),
            (string) $file->getMimeType(),
            $file->getRealPath() ?: null,
        );
    }

    /**
     * Dezelfde toetsen voor een bestand dat niet via een upload binnenkwam
     * (een download, een import): een lokaal pad met de naam waaronder het
     * bewaard gaat worden.
     */
    public static function rejectionReasonForPath(string $path, string $name): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return __('Ongeldig bestand.');
        }

        $file = new File($path, false);

        return static::rejectionReasonFor($name, (string) $file->guessExtension(), (string) $file->getMimeType(), $path);
    }

    /**
     * Extensie en mimetype komen van de aanroeper en niet van het pad: een
     * UploadedFile bepaalt die zelf, en dat gedrag moet voor uploads blijven
     * zoals het was.
     */
    protected static function rejectionReasonFor(string $name, string $guessedExtension, string $mimeType, ?string $path): ?string
    {
        if (static::nameHasBlockedExtension($name)) {
            return __('Dit bestandstype is niet toegestaan.');
        }

        $guessedExtension = strtolower($guessedExtension);
        if ($guessedExtension !== '' && in_array($guessedExtension, static::BLOCKED_EXTENSIONS, true)) {
            return __('De inhoud van dit bestand is niet toegestaan.');
        }

        $mimeType = strtolower($mimeType);
        if ($mimeType !== '' && in_array($mimeType, static::BLOCKED_MIME_TYPES, true)) {
            return __('De inhoud van dit bestand is niet toegestaan.');
        }

        if ($path !== null && static::pathLooksLikeServerCode($path, $mimeType)) {
            return __('De inhoud van dit bestand is niet toegestaan.');
        }

        return null;
    }

    public static function nameHasBlockedExtension(string $name): bool
    {
        $name = strtolower(basename(str_replace('\\', '/', $name)));

        // .htaccess / .user.ini: een naam zonder basis vóór de punt.
        if (in_array(ltrim($name, '.'), static::BLOCKED_EXTENSIONS, true)) {
            return true;
        }

        foreach (array_slice(explode('.', $name), 1) as $segment) {
            if (in_array(trim($segment), static::BLOCKED_EXTENSIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kijkt in het begin van het bestand naar een PHP-openingstag of shebang.
     * Afbeeldingen, PDF's en Office-bestanden bevatten die niet; een
     * "afbeelding" die wél "<?php" bevat is per definitie verdacht.
     */
    public static function contentLooksLikeServerCode(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        return $path ? static::pathLooksLikeServerCode($path) : false;
    }

    /**
     * `<?php` is in binaire data praktisch uitgesloten en telt altijd. De korte
     * tag `<?=` is maar drie bytes en komt in gecomprimeerde beelddata vanzelf
     * voor: gemeten matchte ongeveer 1 op de 300 gewone foto's van 64 kB of
     * groter, die dan als "servercode" geweigerd werden. Voor een bestand dat
     * op de server gesnoven een afbeelding, video, audio, pdf, lettertype of
     * archief is, telt de korte tag daarom alleen als er leesbare tekst op
     * volgt.
     */
    public static function pathLooksLikeServerCode(string $path, ?string $mimeType = null): bool
    {
        if (! is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $head = (string) fread($handle, 64 * 1024);
        fclose($handle);

        if (preg_match('/<\?php\b/i', $head) === 1 || str_starts_with($head, '#!')) {
            return true;
        }

        if (! static::isBinaryMediaType((string) $mimeType)) {
            return str_contains($head, '<?=');
        }

        // In binaire data: alleen als er leesbare code op volgt. Acht leesbare
        // tekens na de drie van de tag komt bij toeval ruwweg eens per miljoen
        // bestanden voor; een echte payload (`<?= system($_GET[1]);`) haalt het altijd.
        // Of een korte, leesbare uitdrukking die netjes met de sluittag eindigt.
        // De sluittag staat hieronder in twee stukken: aaneen geschreven beëindigt
        // hij zelfs binnen een string of commentaar de PHP-modus van dit bestand.
        return preg_match('/<\?=(?:[\x09\x0a\x0d\x20-\x7e]{8,}|[\x09\x0a\x0d\x20-\x7e]{0,200}?\?'.'>)/', $head) === 1;
    }

    protected static function isBinaryMediaType(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);

        // svg is tekst en kan dus wel een korte tag dragen.
        if (str_contains($mimeType, 'svg') || str_contains($mimeType, 'xml')) {
            return false;
        }

        foreach (['image/', 'video/', 'audio/', 'font/'] as $prefix) {
            if (str_starts_with($mimeType, $prefix)) {
                return true;
            }
        }

        // Office-bestanden zijn gecomprimeerde containers en dragen de korte tag
        // dus net zo vaak bij toeval als een foto.
        foreach (['application/vnd.openxmlformats-officedocument.', 'application/vnd.oasis.opendocument.', 'application/vnd.ms-'] as $prefix) {
            if (str_starts_with($mimeType, $prefix)) {
                return true;
            }
        }

        return in_array($mimeType, ['application/pdf', 'application/zip', 'application/gzip', 'application/msword', 'application/font-woff', 'application/x-ole-storage', 'application/cdfv2'], true);
    }
}
