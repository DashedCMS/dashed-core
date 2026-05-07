<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Maakt de tabel aan voor de admin samenvatting-mail-abonnementen.
     * Per user per contributor 1 rij die de gekozen frequentie en
     * volgende verstuur-tijdstempels vasthoudt. Geguard met
     * Schema::hasTable() zodat re-runs en partial migraties veilig zijn.
     */
    public function up(): void
    {
        if (Schema::hasTable('dashed__summary_subscriptions')) {
            return;
        }

        Schema::create('dashed__summary_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('contributor_key', 64);
            $table->string('frequency', 16)->default('off');
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'contributor_key'], 'dashed__summary_subs_user_contributor_unique');
            $table->index(['next_send_at', 'frequency'], 'dashed__summary_subs_next_send_idx');
            $table->index('contributor_key', 'dashed__summary_subs_key_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__summary_subscriptions');
    }
};
