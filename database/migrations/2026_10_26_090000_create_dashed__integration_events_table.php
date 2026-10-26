<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__integration_events')) {
            return;
        }

        // Geslaagde en mislukte synchronisaties per koppeling, voor "laatste
        // geslaagde sync" en "fouten in de laatste 24 uur" op het scherm
        // Koppelingen. Opgeruimd via het bewaartermijnregister.
        Schema::create('dashed__integration_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('site_id')->nullable();
            $table->boolean('ok');
            $table->text('message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['slug', 'site_id', 'ok', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__integration_events');
    }
};
