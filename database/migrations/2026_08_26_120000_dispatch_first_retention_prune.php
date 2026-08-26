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
        // Niet tijdens tests. De suite draait migrate:fresh per testbestand met
        // de wachtrij op sync, dus zou elke test hier een volledige opruimronde
        // over alle veertien entries doen voordat hij aan zijn eigen werk
        // toekomt, en zou een test die zelf oude rijen klaarzet ze al kwijt
        // zijn voordat hij begint.
        if (app()->runningUnitTests()) {
            return;
        }

        PruneAllRetentionsJob::dispatch();
    }

    public function down(): void
    {
        // Opgeruimde rijen komen niet terug. Niets om terug te draaien.
    }
};
