<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__personal_data_requests')) {
            return;
        }

        Schema::create('dashed__personal_data_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('status', 20)->default('new')->index();
            // Na een geslaagde verwijdering leeg; dan blijft alleen de hash.
            $table->string('email')->nullable();
            $table->string('email_hash', 64)->index();
            $table->date('requested_at')->nullable();
            $table->string('verification_method', 40)->nullable();
            $table->text('verification_note')->nullable();
            $table->json('preview')->nullable();
            $table->timestamp('previewed_at')->nullable();
            $table->json('log')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('executed_by')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('file_deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__personal_data_requests');
    }
};
