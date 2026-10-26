<?php

namespace Dashed\DashedCore\Classes\Completeness;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Hoe compleet is een record: per modelklasse een lijst regels die een pakket
 * aanmeldt (ec-core meldt de productregels, Bol de Bol-titel), elk met een
 * gewicht en de vraag of het ontbreken het publiceren tegenhoudt. Dezelfde
 * regels voeden de balk op de bewerkpagina, de kolom en het filter in de
 * lijst, en de publicatiecheck bij opslaan.
 *
 * Aanmelden gebeurt bij elke boot (de labels zijn __()-aanroepen, dus nooit in
 * register()); een regel staat op sleutel, zodat opnieuw aanmelden hem
 * vervangt in plaats van verdubbelt.
 */
class Completeness
{
    /** @var array<class-string, array<string, array{label: string, check: Closure, weight: int, blocking: bool}>> */
    protected static array $rules = [];

    /**
     * @param  class-string  $modelClass
     * @param  Closure(Model): bool  $check  true als het in orde is
     */
    public static function register(string $modelClass, string $key, string $label, Closure $check, int $weight = 1, bool $blocking = false): void
    {
        static::$rules[$modelClass][$key] = [
            'label' => $label,
            'check' => $check,
            'weight' => max(1, $weight),
            'blocking' => $blocking,
        ];
    }

    public static function forget(string $modelClass, ?string $key = null): void
    {
        if ($key === null) {
            unset(static::$rules[$modelClass]);

            return;
        }

        unset(static::$rules[$modelClass][$key]);
    }

    public static function supports(Model|string $model): bool
    {
        return static::rulesFor($model) !== [];
    }

    /**
     * @return array<string, array{label: string, check: Closure, weight: int, blocking: bool}>
     */
    public static function rulesFor(Model|string $model): array
    {
        $class = is_string($model) ? $model : $model::class;

        foreach (static::$rules as $registered => $rules) {
            if ($class === $registered || is_subclass_of($class, $registered)) {
                return $rules;
            }
        }

        return [];
    }

    public static function check(Model $model): CompletenessResult
    {
        $total = 0;
        $earned = 0;
        $missing = [];
        $blocking = [];

        foreach (static::rulesFor($model) as $key => $rule) {
            $total += $rule['weight'];
            $ok = (bool) rescue(fn () => ($rule['check'])($model), false, report: false);

            if ($ok) {
                $earned += $rule['weight'];

                continue;
            }

            $missing[$key] = $rule['label'];

            if ($rule['blocking']) {
                $blocking[$key] = $rule['label'];
            }
        }

        return new CompletenessResult(
            percentage: $total > 0 ? (int) floor($earned / $total * 100) : 100,
            missing: $missing,
            blocking: $blocking,
        );
    }

    /**
     * Geldig GTIN-8, -12, -13 of -14, met controlecijfer. Hier en niet in
     * ec-core, zodat elk pakket dat een EAN kent dezelfde toets gebruikt.
     */
    public static function validGtin(?string $code): bool
    {
        $code = trim((string) $code);

        if (! preg_match('/^\d{8}$|^\d{12,14}$/', $code)) {
            return false;
        }

        $digits = array_map('intval', str_split($code));
        $check = array_pop($digits);
        $sum = 0;

        foreach (array_reverse($digits) as $i => $digit) {
            $sum += $digit * ($i % 2 === 0 ? 3 : 1);
        }

        return (10 - $sum % 10) % 10 === $check;
    }

    public static function flush(): void
    {
        static::$rules = [];
    }
}
