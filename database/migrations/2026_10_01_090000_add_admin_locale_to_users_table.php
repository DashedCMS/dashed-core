<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'admin_locale')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Leeg betekent de standaard uit dashed-core.admin_locale.
            $table->string('admin_locale', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_locale');
        });
    }
};
