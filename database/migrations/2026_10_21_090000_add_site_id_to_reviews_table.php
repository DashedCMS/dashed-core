<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('dashed__reviews') || Schema::hasColumn('dashed__reviews', 'site_id')) {
            return;
        }

        Schema::table('dashed__reviews', function (Blueprint $table) {
            // Leeg betekent: geldt voor elke site. De providers die reviews
            // ophalen kennen geen site; wie een review aan één site wil
            // koppelen vult dit, en het bedrijfsprofiel telt dan per site.
            $table->string('site_id')->nullable()->index()->after('provider');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dashed__reviews', 'site_id')) {
            return;
        }

        Schema::table('dashed__reviews', function (Blueprint $table) {
            $table->dropColumn('site_id');
        });
    }
};
