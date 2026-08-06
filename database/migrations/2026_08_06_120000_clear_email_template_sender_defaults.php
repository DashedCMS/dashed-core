<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/**
 * De afzendervelden op een e-mailtemplate (from_name / from_email) zijn bedoeld
 * als bewuste override: leeg betekent "gebruik site_name en site_from_email uit
 * de algemene instellingen" (zie HasEmailTemplate::templateFrom()). Het
 * formulier documenteert dat ook zo.
 *
 * Oudere versies van ListEmailTemplates::ensureRegisteredMailablesHaveTemplates()
 * vulden beide velden bij het aanmaken automatisch met de op dat moment geldende
 * site-instellingen. Daardoor stond er in vrijwel elke template een gekopieerd
 * adres, en had een latere wijziging van de algemene instellingen geen effect
 * meer op de daadwerkelijke afzender.
 *
 * Deze migratie zet beide velden terug op leeg, zodat de algemene instellingen
 * weer leidend zijn. Wie per template een afwijkende afzender wil, vult het veld
 * daarna handmatig in — dat blijft dan staan.
 *
 * from_name is translatable (Spatie slaat een JSON-locale-map op in de kolom);
 * die wordt daarom in zijn geheel op NULL gezet in plaats van per locale.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('dashed__email_templates')) {
            return;
        }

        DB::table('dashed__email_templates')->update([
            'from_name' => null,
            'from_email' => null,
        ]);
    }

    public function down(): void
    {
        // De oorspronkelijke waarden waren automatisch gekopieerde
        // site-instellingen en zijn niet te herleiden. Leeg is bovendien de
        // gewenste standaard, dus terugdraaien is bewust een no-op.
    }
};
