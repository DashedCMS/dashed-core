<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('dashed__seo_improvements')->truncate();

        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->dropIndex('seo_improvement_subject');
            $table->unique(['subject_type', 'subject_id'], 'seo_improvement_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dashed__seo_improvements', function (Blueprint $table) {
            $table->dropUnique('seo_improvement_subject_unique');
            $table->index(['subject_type', 'subject_id'], 'seo_improvement_subject');
        });
    }
};
