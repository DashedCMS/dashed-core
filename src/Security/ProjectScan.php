<?php

namespace Dashed\DashedCore\Security;

use Symfony\Component\Finder\Finder;

/**
 * Drie snelle scans over de projectbestanden, voor de Beveiligingscheck.
 * Ze lezen alleen; wat ze vinden is een aanwijzing, geen oordeel. De paden
 * zijn parameters zodat een test op een eigen map kan draaien.
 */
class ProjectScan
{
    public const SHELL_FUNCTIONS = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open', 'popen'];

    /**
     * Configbestanden met een env()-aanroep waarvan de terugvalwaarde op een
     * echt geheim lijkt: minstens acht tekens en niet een gewone
     * omgevingswaarde als "local" of een URL. Een geheim als default staat
     * in git en geldt daarmee als gelekt (playbook M3).
     *
     * @return array<int, string> "bestand: VARIABELE"
     */
    public static function secretsInConfig(string $configDir): array
    {
        $found = [];

        foreach (self::phpFiles($configDir, depth: 1) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            if (! preg_match_all("/env\\(\\s*'([A-Z0-9_]+)'\\s*,\\s*'([^']{8,})'\\s*\\)/", $contents, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                if (self::looksLikeSecret($match[1], $match[2])) {
                    $found[] = $file->getRelativePathname() . ': ' . $match[1];
                }
            }
        }

        sort($found);

        return array_values(array_unique($found));
    }

    /**
     * PHP-bestanden in public/ anders dan index.php. Alleen index.php hoort
     * door PHP-FPM uitgevoerd te worden; elk ander bestand is een webshell
     * of een vergeten probe (playbook M14, M21).
     *
     * @return array<int, string>
     */
    public static function strayPhpInPublic(string $publicDir): array
    {
        $found = [];

        foreach (self::phpFiles($publicDir) as $file) {
            if ($file->getRelativePathname() === 'index.php') {
                continue;
            }

            $found[] = $file->getRelativePathname();
        }

        sort($found);

        return $found;
    }

    /**
     * Aanroepen van shell-functies in de eigen code (app/, routes/). Een
     * commentaar of een string telt niet mee; alleen een echte aanroep.
     *
     * @param  array<int, string>  $dirs
     * @return array<int, string> "bestand: functie"
     */
    public static function shellFunctions(array $dirs): array
    {
        $found = [];
        $pattern = '/(?<![\w\\\\>$])(' . implode('|', self::SHELL_FUNCTIONS) . ')\s*\(/';

        foreach ($dirs as $dir) {
            foreach (self::phpFiles($dir) as $file) {
                $tokens = @token_get_all((string) file_get_contents($file->getPathname()));
                $code = '';

                foreach ($tokens as $token) {
                    if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        continue;
                    }

                    $code .= is_array($token) ? $token[1] : $token;
                }

                if (preg_match_all($pattern, $code, $matches)) {
                    foreach (array_unique($matches[1]) as $function) {
                        $found[] = $file->getRelativePathname() . ': ' . $function;
                    }
                }
            }
        }

        sort($found);

        return $found;
    }

    protected static function looksLikeSecret(string $name, string $value): bool
    {
        if (preg_match('/^(local|production|staging|testing|development|true|false|null|database|redis|file|sync|smtp|log|array|cookie|memcached)$/i', $value)) {
            return false;
        }

        if (preg_match('#^(https?://|/|[a-z0-9._-]+@|\d{1,3}(\.\d{1,3}){3}$)#i', $value)) {
            return false;
        }

        // Namen die naar een geheim wijzen tellen altijd; andere namen alleen
        // bij een waarde die op een token lijkt (geen spaties, mix van
        // tekens).
        if (preg_match('/(SECRET|TOKEN|KEY|PASSWORD|PASS|API|CREDENTIAL|PRIVATE)/', $name)) {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z0-9_\-]{16,}$/', $value) && preg_match('/\d/', $value) && preg_match('/[A-Za-z]/', $value);
    }

    /**
     * @return iterable<\Symfony\Component\Finder\SplFileInfo>
     */
    protected static function phpFiles(string $dir, ?int $depth = null): iterable
    {
        if (! is_dir($dir)) {
            return [];
        }

        $finder = Finder::create()->files()->in($dir)->name('*.php')->ignoreVCS(true)->exclude(['vendor', 'node_modules']);

        if ($depth !== null) {
            $finder->depth('< ' . $depth);
        }

        return $finder;
    }
}
