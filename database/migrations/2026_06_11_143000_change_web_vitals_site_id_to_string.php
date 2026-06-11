<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // site_id bevat in de hele Dashed-codebase een string-identifier (de
        // actieve site, bijv. 'ternair'), maar deze tabellen kregen abusievelijk
        // een unsignedBigInteger. Op MySQL geeft dat "Incorrect integer value".
        // Op SQLite (testdatabase) wordt de kolom al via de create-migratie als
        // string aangemaakt en is een wijziging niet nodig.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('dashed__web_vitals', function (Blueprint $table) {
            $table->string('site_id', 64)->nullable()->change();
        });

        Schema::table('dashed__web_vitals_daily', function (Blueprint $table) {
            $table->string('site_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('dashed__web_vitals', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });

        Schema::table('dashed__web_vitals_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });
    }
};
