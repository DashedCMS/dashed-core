<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Dashed\DashedCore\Security\MfaSecretEncryption;

/**
 * Klantprojecten die $casts in App\Models\User overschreven, bewaarden het
 * MFA-geheim onversleuteld. Het model versleutelt nu altijd; deze migratie
 * zet de bestaande waarden om, zodat niemand MFA opnieuw hoeft in te stellen.
 * Een handvol rijen, dus gewoon tijdens de deploy.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'app_authentication_secret')) {
            return;
        }

        MfaSecretEncryption::encryptPlaintext();
    }

    public function down(): void
    {
        // Onversleuteld terugzetten doen we niet.
    }
};
