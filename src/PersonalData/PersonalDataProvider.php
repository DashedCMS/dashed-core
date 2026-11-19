<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

use Illuminate\Support\Facades\Schema;

/**
 * Eén klasse per pakket die weet waar dat pakket persoonsgegevens bewaart.
 * Een pakket meldt hem aan met
 * cms()->registerPersonalDataProvider(MijnProvider::class), achter een
 * method_exists-guard, zodat het met een oudere dashed-core gewoon boot.
 *
 * Per bron (tabel) beslist de provider wat er bij verwijderen gebeurt:
 * verwijderen, anonimiseren met vaste vervangwaarden (zie Anonymized), of
 * bewaren met een reden. find() mag niets wijzigen: dat is het droge
 * overzicht dat de beheerder ziet voordat hij bevestigt.
 *
 * Een abstracte klasse en geen interface, zodat er later een methode bij kan
 * komen zonder dat elke provider in elk pakket breekt.
 */
abstract class PersonalDataProvider
{
    /** Unieke sleutel, meestal de pakketnaam. Ook de mapnaam in de export. */
    abstract public function key(): string;

    /** Naam voor de beheerder. Mag __() gebruiken: pas per verzoek aangeroepen. */
    abstract public function label(): string;

    /**
     * Wat er gevonden is en wat er bij verwijderen mee gebeurt. Wijzigt niets.
     *
     * @return array<int, PersonalDataFinding>
     */
    abstract public function find(PersonalDataSubject $subject): array;

    /** Zet de gegevens in de export, als JSON per bron en eventueel bestanden. */
    abstract public function export(PersonalDataSubject $subject, PersonalDataExport $export): void;

    /**
     * Voert de verwijdering uit en zegt per bron wat er gebeurd is.
     *
     * @return array<int, PersonalDataFinding>
     */
    abstract public function erase(PersonalDataSubject $subject): array;

    /**
     * Volgorde bij verwijderen, laag eerst. Het account zelf (dashed-core)
     * gaat als laatste, zodat andere providers het nog kunnen loskoppelen.
     */
    public function priority(): int
    {
        return 100;
    }

    /** Bestaat de tabel? Niet elk klantproject heeft elke migratie gedraaid. */
    protected function hasTable(string $table): bool
    {
        return rescue(fn () => Schema::hasTable($table), false, report: false);
    }

    /**
     * Rijen voor de export, zonder kolommen die niets over de persoon zeggen
     * maar wel geheim zijn (tokens, wachtwoorden).
     *
     * @param  array<int, string>  $without
     * @return array<int, array<string, mixed>>
     */
    protected function rows(\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query, array $without = []): array
    {
        return $query->get()
            ->map(fn ($row) => array_diff_key(
                $row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array) $row,
                array_flip($without),
            ))
            ->values()
            ->all();
    }
}
