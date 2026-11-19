<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('dashed__ab_tests')) {
            Schema::create('dashed__ab_tests', function (Blueprint $table) {
                $table->id();
                $table->string('site_id');
                $table->string('name');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('locale')->nullable();
                $table->string('block_type');
                $table->json('variant_b')->nullable();
                $table->unsignedTinyInteger('split_b')->default(50);
                $table->string('goal')->default('click');
                $table->string('status')->default('draft');
                $table->string('winner', 1)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['model_type', 'model_id']);
                $table->index(['site_id', 'status']);
            });
        }

        if (! Schema::hasTable('dashed__ab_test_stats')) {
            Schema::create('dashed__ab_test_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ab_test_id')->constrained('dashed__ab_tests')->cascadeOnDelete();
                $table->string('variant', 1);
                $table->date('date');
                $table->unsignedInteger('views')->default(0);
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('conversions')->default(0);

                $table->unique(['ab_test_id', 'variant', 'date'], 'ab_test_stats_unique');
            });
        }

        if (! Schema::hasTable('dashed__ab_test_conversions')) {
            Schema::create('dashed__ab_test_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ab_test_id')->constrained('dashed__ab_tests')->cascadeOnDelete();
                $table->string('variant', 1);
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->timestamp('converted_at')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->unique(['ab_test_id', 'subject_type', 'subject_id'], 'ab_test_conversions_unique');
                $table->index(['subject_type', 'subject_id']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__ab_test_conversions');
        Schema::dropIfExists('dashed__ab_test_stats');
        Schema::dropIfExists('dashed__ab_tests');
    }
};
