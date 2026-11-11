<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Dashed\DashedCore\Classes\GooglePlacesKeyCheck;

return new class () extends Migration {
    public function up(): void
    {
        // De reviews-sync gebruikt voortaan "Places API (New)". Staat er in dit
        // project een key die daar niet op werkt, dan krijgen de beheerders één
        // mail met wat ze moeten doen. Alleen in productie: lokaal en in tests
        // geen verzoeken naar Google en geen mail.
        if (! app()->environment('production') || app()->runningUnitTests() || ! Schema::hasTable('dashed__custom_settings')) {
            return;
        }

        rescue(fn () => GooglePlacesKeyCheck::notifyOutdatedKeys(), report: false);
    }

    public function down(): void
    {
    }
};
