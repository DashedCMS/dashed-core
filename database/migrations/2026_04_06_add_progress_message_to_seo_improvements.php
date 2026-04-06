<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->text('progress_message')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->dropColumn('progress_message');
        });
    }
};
