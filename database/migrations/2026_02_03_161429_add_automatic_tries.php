<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('filament_media_library', function (Blueprint $table) {
            $table->integer('automatic_alt_tries')
                ->default(0);
        });
    }

    public function down(): void
    {
    }
};
