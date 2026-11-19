<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Actiebalken bovenaan de site, met optionele countdown. Zie AnnouncementBars.
        if (! Schema::hasTable('dashed__announcement_bars')) {
            Schema::create('dashed__announcement_bars', function (Blueprint $table) {
                $table->id();
                $table->string('site_id')->nullable();
                $table->string('name');
                $table->boolean('active')->default(true);
                $table->json('text')->nullable();
                $table->json('link_url')->nullable();
                $table->json('link_label')->nullable();
                $table->string('background_color', 16)->nullable();
                $table->string('text_color', 16)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('countdown')->default(false);
                $table->json('page_types')->nullable();
                $table->boolean('dismissible')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['site_id', 'active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__announcement_bars');
    }
};
