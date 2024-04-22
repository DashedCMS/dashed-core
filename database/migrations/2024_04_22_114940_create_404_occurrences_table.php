<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dashed__not_found_pages', function (Blueprint $table) {
            $table->id();

            $table->string('link');
            $table->dateTime('last_occurrence')
                ->nullable();
            $table->integer('total_occurrences')
                ->default(0);
            $table->string('site');
            $table->string('locale');

            $table->timestamps();
        });

        Schema::create('dashed__not_found_page_occurrences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('not_found_page_id')
                ->constrained('dashed__not_found_pages')
                ->cascadeOnDelete();

            $table->integer('status_code');
            $table->string('referer')
                ->nullable();
            $table->string('user_agent')
                ->nullable();
            $table->ipAddress();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('404_occurrences');
    }
};
