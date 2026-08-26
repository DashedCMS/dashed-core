<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Op de meldingen stond alleen een index op de ontvanger. Het opruimen kijkt
 * naar created_at en read_at, en zou zonder deze index elke portie de hele
 * tabel doorlopen; juist op de installaties waar die tabel te groot is
 * geworden. De sleutel is bovendien een uuid, dus de rijen liggen niet op
 * volgorde van binnenkomst en er is geen bereik dat het werk beperkt.
 *
 * Idempotent, want op een aantal servers is deze index met de hand aangelegd
 * voordat de migratie er was; die zouden anders klappen op een dubbele naam.
 */
return new class () extends Migration {
    private string $table = 'notifications';

    /** @var array<string, string> */
    private array $indexen = [
        'notifications_created_at_index' => 'created_at',
        'notifications_read_at_index' => 'read_at',
    ];

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $bestaand = $this->bestaandeIndexen();

        foreach ($this->indexen as $naam => $kolom) {
            if (in_array($naam, $bestaand, true)) {
                continue;
            }

            Schema::table($this->table, function (Blueprint $table) use ($naam, $kolom) {
                $table->index($kolom, $naam);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $bestaand = $this->bestaandeIndexen();

        foreach (array_keys($this->indexen) as $naam) {
            if (! in_array($naam, $bestaand, true)) {
                continue;
            }

            Schema::table($this->table, function (Blueprint $table) use ($naam) {
                $table->dropIndex($naam);
            });
        }
    }

    /**
     * Via de schemabouwer en niet via SHOW INDEXES, want de testsuite draait op
     * sqlite en kent dat commando niet.
     *
     * @return array<int, string>
     */
    private function bestaandeIndexen(): array
    {
        return collect(Schema::getIndexes($this->table))
            ->pluck('name')
            ->filter()
            ->map(fn ($naam) => (string) $naam)
            ->all();
    }
};
