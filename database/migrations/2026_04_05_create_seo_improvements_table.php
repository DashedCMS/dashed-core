<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__seo_improvements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->enum('status', ['analyzing', 'ready', 'applied', 'failed'])->default('analyzing');
            $table->json('keyword_research')->nullable();
            $table->text('analysis_summary')->nullable();
            $table->json('field_proposals')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id'], 'seo_improvement_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__seo_improvements');
    }
};
