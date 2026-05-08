<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__custom_structured_data', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('schema_type');
            $table->longText('json_ld');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['subject_type', 'subject_id', 'schema_type'],
                'custom_structured_data_unique'
            );
            $table->index(['subject_type', 'subject_id'], 'custom_structured_data_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__custom_structured_data');
    }
};
