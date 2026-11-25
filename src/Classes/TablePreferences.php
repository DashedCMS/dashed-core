<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\UserTablePreference;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * Onthoudt per gebruiker hoe hij elke tabel in het paneel heeft ingesteld:
 * filters, sortering, zichtbare kolommen en hun volgorde, aantal per pagina
 * en groepering.
 *
 * Filament bewaart die stand zelf in de sessie (`tables.<md5 klasse>_<soort>`),
 * en `Table::configureUsing()` in de ServiceProvider zet dat voor filters,
 * sortering en groepering aan. Een sessie overleeft alleen geen nieuwe login,
 * dus spiegelt de persistente paneelmiddleware `RememberTablePreferences` de
 * sessiesleutels naar `dashed__user_table_preferences` en zet ze bij de
 * eerste aanvraag van een nieuwe sessie terug. Filament merkt daar niets van:
 * die leest gewoon zijn eigen sessiesleutels.
 *
 * Zoektermen gaan bewust niet mee: een zoekterm van gisteren die een lijst
 * stilletjes leeg laat lijken is verwarrender dan opnieuw typen.
 */
class TablePreferences
{
    public const SESSION_ROOT = 'tables';

    /** Sessiesleutel met het id van de gebruiker wiens stand al is teruggezet. */
    public const LOADED_MARKER = 'dashed.table_preferences_loaded_for';

    /** @var list<string> */
    public const REMEMBERED_SUFFIXES = [
        '_filters',
        '_sort',
        '_columns',
        '_has_reordered_columns',
        '_per_page',
        '_grouping',
    ];

    public static function isRemembered(string $key): bool
    {
        return Str::endsWith($key, self::REMEMBERED_SUFFIXES);
    }

    /**
     * Zet de opgeslagen stand van de gebruiker eenmalig per sessie terug. Een
     * sleutel die de sessie al heeft wint: die is nieuwer.
     */
    public static function restore(Session $session, int|string $userId): void
    {
        if ((string) $session->get(self::LOADED_MARKER) === (string) $userId) {
            return;
        }

        UserTablePreference::query()
            ->where('user_id', $userId)
            ->get(['key', 'value'])
            ->each(function (UserTablePreference $preference) use ($session): void {
                if (! self::isRemembered($preference->key)) {
                    return;
                }

                $sessionKey = self::SESSION_ROOT.'.'.$preference->key;

                if (! $session->has($sessionKey)) {
                    $session->put($sessionKey, $preference->value);
                }
            });

        $session->put(self::LOADED_MARKER, (string) $userId);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Session $session): array
    {
        $tables = $session->get(self::SESSION_ROOT, []);

        if (! is_array($tables)) {
            return [];
        }

        return array_filter(
            $tables,
            fn ($value, $key): bool => is_string($key) && self::isRemembered($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Schrijft weg wat er in dit verzoek veranderde. Vergeleken op JSON, want
     * na een rondgang door de database is `1.0` een `1` en hoort dat geen
     * schrijfactie op elke paginalading op te leveren.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function persist(int|string $userId, array $before, array $after): void
    {
        foreach ($after as $key => $value) {
            if (array_key_exists($key, $before) && json_encode($before[$key]) === json_encode($value)) {
                continue;
            }

            UserTablePreference::query()->updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $value],
            );
        }

        $removed = array_keys(array_diff_key($before, $after));

        if ($removed !== []) {
            UserTablePreference::query()
                ->where('user_id', $userId)
                ->whereIn('key', $removed)
                ->delete();
        }
    }
}
