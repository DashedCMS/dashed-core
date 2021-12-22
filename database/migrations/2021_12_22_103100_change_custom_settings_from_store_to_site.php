<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCustomSettingsFromStoreToSite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customSettings = \Qubiqx\QcommerceCore\Models\Customsetting::all();
        foreach ($customSettings as $customSetting) {
            $customSetting->name = str_replace('store', 'site', $customSetting->name);
            $customSetting->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
