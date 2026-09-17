<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Migrations\Migration;

/**
 * Het logboek van verzonden mails bewaarde tot nu toe elke body letterlijk,
 * dus ook het wachtwoord uit de accountmail en de tokens uit resetlinks.
 * Deze migratie zet het opschonen van de bestaande rijen eenmalig in de
 * wachtrij, zodat de deploy er niet op wacht. Zie SentEmailScrubber.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        dispatch(fn () => Artisan::call('dashed:scrub-sent-emails'));
    }

    public function down(): void
    {
        // Weggehaalde geheimen komen niet terug; niets terug te draaien.
    }
};
