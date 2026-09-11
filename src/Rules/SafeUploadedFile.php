<?php

namespace Dashed\DashedCore\Rules;

use Closure;
use Illuminate\Http\UploadedFile;
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
        if (static::nameHasBlockedExtension((string) $file->getClientOriginalName())) {
            return __('Dit bestandstype is niet toegestaan.');
        }

        $guessedExtension = strtolower((string) $file->guessExtension());
        if ($guessedExtension !== '' && in_array($guessedExtension, static::BLOCKED_EXTENSIONS, true)) {
            return __('De inhoud van dit bestand is niet toegestaan.');
        }

        $mimeType = strtolower((string) $file->getMimeType());
        if ($mimeType !== '' && in_array($mimeType, static::BLOCKED_MIME_TYPES, true)) {
            return __('De inhoud van dit bestand is niet toegestaan.');
        }

        if (static::contentLooksLikeServerCode($file)) {
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
        if (! $path || ! is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $head = (string) fread($handle, 64 * 1024);
        fclose($handle);

        return (bool) preg_match('/<\?(php\b|=)/i', $head) || str_starts_with($head, '#!');
    }
}
