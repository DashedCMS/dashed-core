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

        Schema::table('dashed__url_history', function (Blueprint $table) {
            $table->index(['locale', 'site_id', 'model_type', 'model_id', 'batch'], 'index_l_s_m_m_b');
            $table->index(['url', 'locale', 'site_id'], 'index_u_l_s');
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
