<?php

use Dashed\DashedCore\Classes\UploadSecurity;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * De tijdelijke Livewire-uploads stonden tot nu toe op de standaarddisk,
     * in de meeste projecten `public`, uitgeserveerd via /storage. Nu de
     * tijdelijke disk `local` is, gaat de oude map met wat er nog in staat
     * weg (in een klantproject is daar een geupload .php-bestand in
     * gevonden), en komt er een .htaccess dat scriptuitvoering in
     * storage/app/public uitschakelt.
     */
    public function up(): void
    {
        UploadSecurity::hardenPublicStorage();
    }

    public function down(): void
    {
        // Niets terug te zetten: de map bevatte alleen verlopen tijdelijke uploads.
    }
};
