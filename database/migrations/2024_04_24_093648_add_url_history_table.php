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
        Schema::create('dashed__url_history', function (Blueprint $table) {
            $table->id();

            $table->integer('batch');
            $table->string('method'); //usually ->getUrl()
            $table->string('url');
            $table->string('site_id');
            $table->string('locale');
            $table->morphs('model');

            $table->timestamps();
            $table->softDeletes();
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
