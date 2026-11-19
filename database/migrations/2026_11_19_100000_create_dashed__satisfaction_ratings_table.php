<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__satisfaction_ratings')) {
            return;
        }

        Schema::create('dashed__satisfaction_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('site_id')->nullable()->index();
            $table->string('source', 30)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('staff_user_id')->nullable()->index();
            $table->string('staff_name')->nullable();
            $table->string('email')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('token', 40)->unique();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('rated_at')->nullable()->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['source', 'rated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__satisfaction_ratings');
    }
};
