<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\Artisan::call('dashed:replace-editor-strings-in-files');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
