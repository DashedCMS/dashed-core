<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('dashed__login_attempts', 'url')) {
            return;
        }

        Schema::table('dashed__login_attempts', function (Blueprint $table) {
            // De URL waar de poging vandaan kwam (bij een Livewire-verzoek de
            // pagina, niet de Livewire-route). De kolom staat ook in de
            // create-migratie, want die draait op verse installaties na deze.
            $table->text('url')->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('dashed__login_attempts', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
