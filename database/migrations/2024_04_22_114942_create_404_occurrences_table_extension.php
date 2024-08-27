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
        Schema::table('dashed__not_found_pages', function (Blueprint $table) {
            $table->text('link')
                ->change();
        });

        Schema::table('dashed__not_found_page_occurrences', function (Blueprint $table) {
            $table->text('referer')
                ->change()
                ->nullable();
            $table->text('user_agent')
                ->change()
                ->nullable();
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
