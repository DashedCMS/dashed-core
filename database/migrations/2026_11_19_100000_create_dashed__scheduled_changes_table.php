<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Ingeplande wijzigingen op een record (zie ScheduledChange en
        // ScheduledChangeRunner). `changes` zijn de bewerkingen zoals ze
        // ingepland zijn, `original` en `applied` worden bij het uitvoeren
        // vastgelegd, zodat terugdraaien weet waarheen en kan zien of iemand
        // het veld sindsdien zelf heeft aangepast.
        if (! Schema::hasTable('dashed__scheduled_changes')) {
            Schema::create('dashed__scheduled_changes', function (Blueprint $table) {
                $table->id();
                $table->string('model_type');
                $table->string('model_id');
                $table->string('label')->nullable();
                $table->string('locale', 10)->nullable();
                $table->json('changes');
                $table->json('original')->nullable();
                $table->json('applied')->nullable();
                $table->json('skipped')->nullable();
                $table->timestamp('run_at');
                $table->timestamp('revert_at')->nullable();
                $table->string('status', 20)->default('scheduled');
                $table->timestamp('applied_at')->nullable();
                $table->timestamp('reverted_at')->nullable();
                $table->text('error')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->timestamps();

                $table->index(['status', 'run_at']);
                $table->index(['status', 'revert_at']);
                $table->index(['model_type', 'model_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__scheduled_changes');
    }
};
