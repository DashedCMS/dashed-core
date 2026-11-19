<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Eén bulkbewerking (zie BulkEditor) met per record de oude en nieuwe
        // waarden, zodat "Ongedaan maken" precies kan terugzetten wat deze
        // run veranderde en niets daarna.
        if (! Schema::hasTable('dashed__bulk_edit_runs')) {
            Schema::create('dashed__bulk_edit_runs', function (Blueprint $table) {
                $table->id();
                $table->string('model_type');
                $table->string('label')->nullable();
                $table->string('locale', 10)->nullable();
                $table->json('changes');
                $table->json('record_ids');
                $table->string('status', 20)->default('queued');
                $table->unsignedInteger('total')->default(0);
                $table->unsignedInteger('done')->default(0);
                $table->unsignedInteger('changed')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('undo_skipped')->default(0);
                $table->text('error')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->timestamp('undone_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('dashed__bulk_edit_items')) {
            Schema::create('dashed__bulk_edit_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bulk_edit_run_id')->constrained('dashed__bulk_edit_runs')->cascadeOnDelete();
                $table->string('model_id');
                $table->json('old');
                $table->json('new');
                $table->json('skipped')->nullable();
                $table->timestamp('undone_at')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['bulk_edit_run_id', 'undone_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__bulk_edit_items');
        Schema::dropIfExists('dashed__bulk_edit_runs');
    }
};
