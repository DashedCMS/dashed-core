<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // FrontendController zoekt op `from` bij elke URL die nergens anders
        // matcht, Redirect::handleSlugChange() op `to` bij elke slugwijziging.
        // Zonder index was dat een volledige tabelscan (oaza: 253k rijen).
        Schema::table('dashed__redirects', function (Blueprint $table) {
            if (! $this->hasIndex('dashed__redirects', 'dashed__redirects_from_index')) {
                $table->index('from');
            }
            if (! $this->hasIndex('dashed__redirects', 'dashed__redirects_to_index')) {
                $table->index('to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dashed__redirects', function (Blueprint $table) {
            $table->dropIndex(['from']);
            $table->dropIndex(['to']);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $indexes = collect(DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=?", [$table]))
                ->pluck('name');
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                ->pluck('Key_name')
                ->unique();
        }

        return $indexes->contains($indexName);
    }
};
