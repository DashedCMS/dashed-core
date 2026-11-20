<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menugebruik per beheerder (zie NavigationUsage): bezoeken per menu-item per
 * dag, zodat de volgorde in de zijbalk op de laatste maanden kan sorteren in
 * plaats van op alles ooit, en de items die iemand zelf bovenaan vastpint.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dashed__navigation_visits')) {
            Schema::create('dashed__navigation_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                // Het pad van het menu-item, zonder query en zonder slash aan het eind.
                $table->string('item_key');
                $table->date('date');
                $table->unsignedInteger('visits')->default(0);

                $table->unique(['user_id', 'item_key', 'date']);
                $table->index('date');
            });
        }

        if (! Schema::hasTable('dashed__navigation_pins')) {
            Schema::create('dashed__navigation_pins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('item_key');
                $table->timestamps();

                $table->unique(['user_id', 'item_key']);
            });
        }

        if (! Schema::hasColumn('users', 'navigation_sort_by_usage')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('navigation_sort_by_usage')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__navigation_pins');
        Schema::dropIfExists('dashed__navigation_visits');

        if (Schema::hasColumn('users', 'navigation_sort_by_usage')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('navigation_sort_by_usage');
            });
        }
    }
};
