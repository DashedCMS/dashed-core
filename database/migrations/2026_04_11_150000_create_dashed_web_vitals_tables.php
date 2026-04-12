<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__web_vitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('metric', 10);
            $table->double('value');
            $table->string('rating', 20)->nullable();
            $table->string('url', 500);
            $table->string('device', 10);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['site_id', 'metric', 'created_at']);
            $table->index(['site_id', 'url']);
        });

        Schema::create('dashed__web_vitals_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->date('date');
            $table->string('metric', 10);
            $table->string('url_pattern', 500);
            $table->string('device', 10);
            $table->double('p75');
            $table->unsignedInteger('sample_count');
            $table->timestamps();

            $table->unique(['site_id', 'date', 'metric', 'url_pattern', 'device'], 'vitals_daily_unique');
            $table->index(['site_id', 'metric', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__web_vitals_daily');
        Schema::dropIfExists('dashed__web_vitals');
    }
};
