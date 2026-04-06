<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->json('block_proposals')->nullable()->after('field_proposals');
        });
    }

    public function down(): void
    {
        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->dropColumn('block_proposals');
        });
    }
};
