<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('dashed__webhook_subscriptions')
                ->cascadeOnDelete();
            $table->string('event', 100);
            $table->json('payload');
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__webhook_deliveries');
    }
};
