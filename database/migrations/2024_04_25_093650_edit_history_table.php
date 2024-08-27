<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        \Dashed\DashedCore\Models\UrlHistory::all()->each(function ($history) {
            $history->delete();
        });

        Schema::table('dashed__url_history', function (Blueprint $table) {
            $table->dropColumn('batch');
            $table->string('previous_url')
                ->nullable()
                ->after('method');
            $table->string('url')
                ->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
