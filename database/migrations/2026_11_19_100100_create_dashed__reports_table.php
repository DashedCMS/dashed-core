<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__reports')) {
            return;
        }

        Schema::create('dashed__reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source', 60);
            $table->json('measures')->nullable();
            $table->string('grouping', 60)->nullable();
            $table->json('filters')->nullable();
            $table->string('period', 30)->default('last_30_days');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('chart', 10)->default('bar');
            $table->string('schedule', 10)->default('none');
            $table->string('schedule_format', 5)->default('xlsx');
            $table->json('recipients')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__reports');
    }
};
