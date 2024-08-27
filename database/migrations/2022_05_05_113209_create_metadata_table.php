<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashed__metadata', function (Blueprint $table) {
            $table->id();
            $table->json('image')->nullable();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('sitemap_priority')->nullable();
            $table->boolean('noindex')->default(false);
            $table->morphs('metadatable');
            $table->timestamps();
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
