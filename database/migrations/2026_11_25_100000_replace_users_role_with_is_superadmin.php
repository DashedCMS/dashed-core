<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Toegang tot het CMS komt voortaan alleen uit gekoppelde rollen, met daarnaast
 * een losse vlag is_superadmin. De kolom users.role (customer/admin/superadmin)
 * verdwijnt.
 *
 * - role = superadmin wordt is_superadmin = true.
 * - role = admin met minstens één rol houdt zijn toegang via die rollen.
 * - role = admin zonder rol wordt klant. Zo iemand had sinds maart 2026 al geen
 *   rechten meer (alleen nog paneeltoegang zonder permissies), dus er gaat geen
 *   werkbare toegang verloren; ze worden wel gelogd zodat een beheerder ze een
 *   rol kan geven.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_superadmin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_superadmin')->default(false);
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')->where('role', 'superadmin')->update(['is_superadmin' => true]);

        $metRol = Schema::hasTable('dashed__model_has_roles')
            ? DB::table('dashed__model_has_roles')->select('user_id')
            : null;

        $zonderRol = DB::table('users')
            ->where('role', 'admin')
            ->when($metRol, fn ($q) => $q->whereNotIn('id', $metRol))
            ->get(['id', 'email']);

        foreach ($zonderRol as $user) {
            $melding = "Gebruiker #{$user->id} ({$user->email}) had role=admin zonder gekoppelde rol en is nu klant.";
            Log::warning($melding);

            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                echo $melding.PHP_EOL;
            }
        }

        if (! DB::table('users')->where('is_superadmin', true)->exists()) {
            $melding = 'Na deze migratie is er geen enkele superadmin. Zet er een met: php artisan dashed:create-admin-user (of is_superadmin = 1 in de database).';
            Log::warning($melding);

            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                echo $melding.PHP_EOL;
            }
        }

        // Op SQLite is een kolom weghalen een herbouw van de tabel, en met
        // foreign keys aan neemt het weggooien van de oude tabel de rijen in
        // dashed__model_has_roles (cascade) mee.
        Schema::withoutForeignKeyConstraints(fn () => Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        }));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('customer');
            });
        }

        if (! Schema::hasColumn('users', 'is_superadmin')) {
            return;
        }

        if (Schema::hasTable('dashed__model_has_roles')) {
            DB::table('users')
                ->whereIn('id', DB::table('dashed__model_has_roles')->select('user_id'))
                ->update(['role' => 'admin']);
        }

        DB::table('users')->where('is_superadmin', true)->update(['role' => 'superadmin']);

        Schema::withoutForeignKeyConstraints(fn () => Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        }));
    }
};
