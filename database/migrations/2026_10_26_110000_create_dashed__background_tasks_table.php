<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashed__background_tasks')) {
            return;
        }

        // Eén rij per taak op de achtergrond (export, vertaling, Bol-titels,
        // nieuwsbrief, opruimronde), voor het takenicoon in de topbalk. Zie
        // BackgroundTask. Opgeruimd via het bewaartermijnregister.
        Schema::create('dashed__background_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('label');
            $table->foreignId('user_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('done')->default(0);
            $table->unsignedInteger('total')->nullable();
            $table->text('message')->nullable();
            $table->string('result_url', 2048)->nullable();
            $table->string('result_label')->nullable();
            $table->boolean('notify')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__background_tasks');
    }
};
