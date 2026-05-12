<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__job_failure_log', function (Blueprint $table) {
            $table->id();
            $table->string('job_class', 191);
            $table->char('trace_hash', 12);
            $table->date('occurred_on');
            $table->unsignedInteger('count')->default(0);
            $table->text('last_message')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['job_class', 'trace_hash', 'occurred_on']);
            $table->index('job_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__job_failure_log');
    }
};
