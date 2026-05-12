<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__webhook_log')) {
            return;
        }

        Schema::create('dashed__webhook_log', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 191);
            $table->char('payload_hash', 64);
            $table->unsignedInteger('site_id')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            // String instead of ENUM so SQLite-in-memory tests honour the
            // default; production MySQL keeps the same value set:
            // received | processing | succeeded | failed.
            $table->string('status', 16)->default('received');
            $table->text('error')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id'], 'uniq_provider_event');
            $table->index('received_at');
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__webhook_log');
    }
};
