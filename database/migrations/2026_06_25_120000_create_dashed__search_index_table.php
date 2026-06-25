<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Gedenormaliseerde zoekindex: per doorzoekbaar model, per locale, één rij met
 * samengevoegde lowercased tekst. Op MySQL ligt er een FULLTEXT-index op
 * search_text zodat MATCH AGAINST de index gebruikt; SQLite (tests) valt terug
 * op LIKE. Verandert niets aan bestaande scopeSearch-filters.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__search_index')) {
            return;
        }

        Schema::create('dashed__search_index', function (Blueprint $table): void {
            $table->id();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('locale', 8)->index();
            $table->text('search_text');
            $table->string('keywords')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['searchable_type', 'searchable_id', 'locale'], 'search_index_morph_locale_unique');
            $table->index(['searchable_type', 'searchable_id'], 'search_index_morph_index');
        });

        // FULLTEXT bestaat alleen op MySQL; SQLite gebruikt de LIKE-fallback.
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('dashed__search_index', function (Blueprint $table): void {
                $table->fullText('search_text', 'search_index_fulltext');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__search_index');
    }
};
