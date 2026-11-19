<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Campagnelinks met UTM-velden en een korte link /k/{code}. Zie ShortLinks.
        if (! Schema::hasTable('dashed__short_links')) {
            Schema::create('dashed__short_links', function (Blueprint $table) {
                $table->id();
                $table->string('site_id')->nullable();
                $table->string('name');
                $table->string('code', 32)->unique();
                $table->string('destination_url', 2048);
                $table->string('channel')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('utm_term')->nullable();
                $table->string('utm_content')->nullable();
                $table->unsignedInteger('click_count')->default(0);
                $table->timestamp('last_clicked_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'utm_campaign']);
            });
        }

        // Kliks per link per dag: een teller, geen IP, geen user agent.
        if (! Schema::hasTable('dashed__short_link_clicks')) {
            Schema::create('dashed__short_link_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('short_link_id');
                $table->date('date');
                $table->unsignedInteger('clicks')->default(0);

                $table->unique(['short_link_id', 'date']);
                $table->index('date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__short_link_clicks');
        Schema::dropIfExists('dashed__short_links');
    }
};
