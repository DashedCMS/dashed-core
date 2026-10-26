<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Dashed\DashedCore\Retention\PrullenbakOpruimer;

return new class () extends Migration {
    public function up(): void
    {
        // Vanaf nu ruimt de prullenbak na dertig dagen op; wat al langer
        // soft-deleted was, blijft staan tot iemand het met de hand weggooit.
        if (Schema::hasTable('dashed__custom_settings')) {
            rescue(fn () => PrullenbakOpruimer::introducedAt(), report: false);
        }
    }

    public function down(): void
    {
    }
};
