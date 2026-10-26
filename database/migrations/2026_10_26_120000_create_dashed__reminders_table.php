<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // "Herinner mij" en "Taak voor..." op een bewerk- of weergavepagina.
        // Zie Reminder en ReminderSender.
        if (! Schema::hasTable('dashed__reminders')) {
            Schema::create('dashed__reminders', function (Blueprint $table) {
                $table->id();
                $table->string('remindable_type')->nullable();
                $table->string('remindable_id')->nullable();
                $table->foreignId('user_id');
                $table->foreignId('created_by')->nullable();
                $table->string('label');
                $table->string('url', 2048)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('remind_at');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'completed_at', 'remind_at']);
                $table->index(['sent_at', 'remind_at']);
                $table->index(['remindable_type', 'remindable_id']);
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'reminders_by_mail')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('reminders_by_mail')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__reminders');

        if (Schema::hasColumn('users', 'reminders_by_mail')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('reminders_by_mail'));
        }
    }
};
