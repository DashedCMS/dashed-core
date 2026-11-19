<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Volledige momentopnames van een CMS-record bij elke opslag, plus de
        // concepten van de goedkeuringsstroom. Zie Revisions en ContentApproval.
        if (Schema::hasTable('dashed__revisions')) {
            return;
        }

        Schema::create('dashed__revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revisionable_type');
            $table->string('revisionable_id');
            $table->foreignId('user_id')->nullable();
            $table->string('status', 20)->default('published');
            $table->string('reason', 30)->nullable();
            $table->unsignedBigInteger('restored_from_id')->nullable();
            $table->unsignedBigInteger('base_revision_id')->nullable();
            $table->longText('snapshot');
            $table->string('hash', 32);
            $table->foreignId('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->index(['revisionable_type', 'revisionable_id', 'status'], 'dashed__revisions_record_status');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__revisions');
    }
};
