<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    private const TABLE = 'dashed__not_found_pages';

    private const INDEX = 'dashed__not_found_pages_lookup_index';

    public function up(): void
    {
        // NotFoundPage::saveOccurrence() zoekt bij elke 404 op deze drie kolommen,
        // en de tabel had alleen een primaire sleutel (gemeten: 69k rijen, type ALL).
        if (! Schema::hasTable(self::TABLE) || Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        // `link` is TEXT, dus MySQL en MariaDB eisen een prefix. Een prefix is
        // alleen een voorselectie: de database vergelijkt de hele waarde nog.
        $columns = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? '`link`(191), `site`(64), `locale`(16)'
            : 'link, site, locale';

        DB::statement('CREATE INDEX ' . self::INDEX . ' ON ' . self::TABLE . " ({$columns})");
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE) && Schema::hasIndex(self::TABLE, self::INDEX)) {
            Schema::table(self::TABLE, fn ($table) => $table->dropIndex(self::INDEX));
        }
    }
};
