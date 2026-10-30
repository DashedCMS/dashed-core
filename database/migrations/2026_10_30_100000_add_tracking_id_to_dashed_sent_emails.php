<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('dashed__sent_emails', 'tracking_id')) {
            return;
        }

        Schema::table('dashed__sent_emails', function (Blueprint $table) {
            $table->string('tracking_id', 32)->nullable()->index()->after('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('dashed__sent_emails', function (Blueprint $table) {
            $table->dropColumn('tracking_id');
        });
    }
};
