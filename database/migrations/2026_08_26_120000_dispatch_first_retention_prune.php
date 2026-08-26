<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Dashed\DashedCore\Jobs\PruneAllRetentionsJob;

/**
 * Zet de eerste opruimronde in de wachtrij. De migratie zelf is meteen klaar,
 * dus de deploy wacht nergens op; Horizon pakt de job op en werkt hem in
 * porties af.
 */
return new class () extends Migration {
    public function up(): void
    {
        PruneAllRetentionsJob::dispatch();
    }

    public function down(): void
    {
        // Opgeruimde rijen komen niet terug. Niets om terug te draaien.
    }
};
