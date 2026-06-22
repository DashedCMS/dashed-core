<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Web Vitals-feature is volledig verwijderd uit het CMS.
        Schema::dropIfExists('dashed__web_vitals');
        Schema::dropIfExists('dashed__web_vitals_daily');
    }

    public function down(): void
    {
        // Bewust niet terugdraaibaar: de feature en zijn tabellen zijn verwijderd.
    }
};
