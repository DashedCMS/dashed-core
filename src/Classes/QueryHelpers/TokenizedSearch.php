<?php

namespace Dashed\DashedCore\Classes\QueryHelpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eén definitie van "breed zoeken" voor het hele CMS.
 *
 * De zoekterm wordt op woorden gesplitst; elk woord moet ergens matchen, alle
 * woorden samen (AND). Zo vindt "koppel 25cm" een product dat "Koppel -
 * zwart/wit - 25cm" heet, waar een LIKE op de hele term niets vindt omdat er
 * tekst tussen de woorden staat.
 *
 * Een streepjescode is geen zin maar één waarde, dus die krijgt daarnaast een
 * exacte match op de volledige zoekterm.
 */
class TokenizedSearch
{
    /**
     * @param  array<int, string>  $columns  Kolommen waarin elk woord mag matchen, in volgorde van belang.
     * @param  array<int, string>  $exactColumns  Kolommen die exact gelijk mogen zijn aan de hele zoekterm.
     * @param  array<string, array<int, string>>  $relations  Relatienaam => kolommen waarin een woord ook mag matchen.
     */
    public static function apply(Builder $query, ?string $search, array $columns, array $exactColumns = [], array $relations = []): Builder
    {
        $search = trim((string) $search);
        $columns = array_values($columns);

        if ($search === '' || $columns === []) {
            return $query;
        }

        $needle = mb_strtolower($search);
        $terms = preg_split('/\s+/', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [$needle];

        // Alles wat met zoeken te maken heeft in één groep, zodat filters die de
        // aanroeper er zelf omheen zet (prijs, site, zichtbaarheid) niet lekken.
        $query->where(function ($group) use ($columns, $terms, $exactColumns, $relations, $search) {
            $isFirst = true;

            foreach ($exactColumns as $column) {
                $group->{$isFirst ? 'where' : 'orWhere'}($column, $search);
                $isFirst = false;
            }

            $group->{$isFirst ? 'where' : 'orWhere'}(function ($all) use ($columns, $terms, $relations) {
                foreach ($terms as $term) {
                    $all->where(function ($outer) use ($columns, $term, $relations) {
                        $outer->where(fn ($q) => self::matchTerm($q, $columns, $term));

                        foreach ($relations as $relation => $relationColumns) {
                            $outer->orWhereHas($relation, function ($q) use ($relationColumns, $term) {
                                $q->where(fn ($qq) => self::matchTerm($qq, array_values($relationColumns), $term));
                            });
                        }
                    });
                }
            });
        });

        return self::orderByRelevance($query, $columns, $exactColumns, $terms, $needle, $search);
    }

    /**
     * De vertaalbare kolommen van een model, zonder de attributen die eigenlijk
     * een relatie zijn.
     *
     * @return array<int, string>
     */
    public static function translatableColumns(Model $model): array
    {
        return collect($model->getTranslatableAttributes())
            ->reject(fn ($attribute) => method_exists($model, $attribute))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected static function matchTerm($query, array $columns, string $term): void
    {
        foreach ($columns as $index => $column) {
            $query->{$index === 0 ? 'whereRaw' : 'orWhereRaw'}("LOWER(`{$column}`) LIKE ?", ["%{$term}%"]);
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $exactColumns
     * @param  array<int, string>  $terms
     */
    protected static function orderByRelevance(Builder $query, array $columns, array $exactColumns, array $terms, string $needle, string $search): Builder
    {
        $cases = [];
        $bindings = [];
        $maximum = 0;

        $phraseWeight = count($terms) + 1;

        foreach ($columns as $index => $column) {
            $weight = count($columns) - $index;

            // De hele zoekterm aaneengesloten telt zwaarder dan de losse woorden,
            // zodat een letterlijke treffer bovenaan blijft staan.
            $phraseScore = $weight * $phraseWeight;
            $cases[] = 'CASE WHEN LOWER(`' . $column . '`) LIKE ? THEN ' . $phraseScore . ' ELSE 0 END';
            $bindings[] = "%{$needle}%";
            $maximum += $phraseScore;

            if (count($terms) > 1) {
                foreach ($terms as $term) {
                    $cases[] = "CASE WHEN LOWER(`{$column}`) LIKE ? THEN {$weight} ELSE 0 END";
                    $bindings[] = "%{$term}%";
                    $maximum += $weight;
                }
            }
        }

        // Een exacte treffer moet boven élke combinatie van losse treffers
        // uitkomen, anders duwt een product dat de streepjescode toevallig in
        // naam én slug heeft staan de gescande regel naar beneden. Daarom wordt
        // dit gewicht uitgerekend en niet geschat.
        $exactCases = [];
        $exactBindings = [];

        foreach ($exactColumns as $column) {
            $exactCases[] = "CASE WHEN `{$column}` = ? THEN " . ($maximum + 1) . ' ELSE 0 END';
            $exactBindings[] = $search;
        }

        $cases = array_merge($exactCases, $cases);
        $bindings = array_merge($exactBindings, $bindings);

        $score = '(' . implode(' + ', $cases) . ')';

        // Sorteren op de uitdrukking zelf, niet op de alias. Een aanroeper die ná
        // search() nog een eigen select() zet, gooit de alias namelijk weg
        // (select() vervangt de kolomlijst) terwijl de sortering blijft staan;
        // MySQL viel daar hard over om ("Unknown column 'relevance' in order
        // clause") en SQLite las de alias stilzwijgend als tekst en sorteerde dus
        // helemaal niet. De alias blijft er wel bij, voor wie de score wil uitlezen.
        $query->select($query->getQuery()->columns ?: ['*'])
            ->selectRaw($score . ' as relevance', $bindings)
            ->orderByRaw($score . ' desc', $bindings);

        return $query;
    }
}
