<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Dashed\DashedCore\Jobs\PruneAllRetentionsJob;

/**
 * De printerbewaking stuurde haar offline-melding naar elke gebruiker, klanten
 * inbegrepen, en liet zo op één installatie 2,2 miljoen rijen in
 * `notifications` achter. Deze ronde haalt de meldingen van klanten weg en
 * brengt elke ontvanger terug naar de grens per persoon. In de wachtrij en
 * niet hier: miljoenen rijen weghalen mag de deploy niet ophouden.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Zelfde reden als bij de eerste opruimronde: de suite draait
        // migrate:fresh per testbestand met de wachtrij op sync.
        if (app()->runningUnitTests()) {
            return;
        }

        PruneAllRetentionsJob::dispatch('notifications');
    }

    public function down(): void
    {
        // Opgeruimde rijen komen niet terug. Niets om terug te draaien.
    }
};
