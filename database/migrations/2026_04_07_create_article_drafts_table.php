<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__article_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('locale', 10)->default('nl');
            $table->text('instruction')->nullable();
            $table->string('status')->default('pending'); // pending, planning, writing, ready, applied, failed
            $table->text('progress_message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('content_plan')->nullable();   // keyword research + outline
            $table->json('article_content')->nullable(); // full generated article
            $table->nullableMorphs('subject');          // optional link to existing Article
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__article_drafts');
    }
};
