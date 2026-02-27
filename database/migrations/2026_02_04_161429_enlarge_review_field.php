<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dashed__reviews', function (Blueprint $table) {
            $table->text('review')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
    }
};
