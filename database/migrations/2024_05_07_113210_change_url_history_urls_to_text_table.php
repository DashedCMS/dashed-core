<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dashed__url_history', function (Blueprint $table) {
            $table->dropIndex('index_u_l_s');
        });

        Schema::table('dashed__url_history', function (Blueprint $table) {
            $table->text('previous_url')
                ->nullable()
                ->change();
            $table->text('url')
                ->nullable()
                ->change();
        });

        Schema::table('dashed__url_history', function (Blueprint $table) {
            $table->index([\Illuminate\Support\Facades\DB::raw('url(255)'), 'locale', 'site_id'], 'index_u_l_s');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metadata');
    }
};
