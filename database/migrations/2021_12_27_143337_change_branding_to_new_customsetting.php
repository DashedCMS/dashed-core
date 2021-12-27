<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Qubiqx\QcommerceCore\Models\Customsetting;

class ChangeBrandingToNewCustomsetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $logoCustomSetting = Customsetting::where('name', 'site_name')->first();
        if ($logoCustomSetting) {
            $logo = \Illuminate\Support\Facades\DB::table('media')
                ->where('model_type', 'Qubiqx\Qcommerce\Models\Customsetting')
                ->where('model_id', $logoCustomSetting->id)
                ->where('collection_name', 'logo')
                ->first();

            if ($logo) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists("/qcommerce/uploads/$logo->id/$logo->file_name")) {
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->copy("/qcommerce/uploads/$logo->id/$logo->file_name", "/qcommerce/branding/logo/$logo->file_name");
                    } catch (Exception $exception) {

                    }
                    foreach (\Qubiqx\QcommerceCore\Classes\Sites::getSites() as $site) {
                        Customsetting::set('site_logo', "/qcommerce/branding/logo/$logo->file_name", $site['id']);
                    }
                }
            }

            $favicon = \Illuminate\Support\Facades\DB::table('media')
                ->where('model_type', 'Qubiqx\Qcommerce\Models\Customsetting')
                ->where('model_id', $logoCustomSetting->id)
                ->where('collection_name', 'favicon')
                ->first();

            if ($favicon) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists("/qcommerce/uploads/$favicon->id/$favicon->file_name")) {
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->copy("/qcommerce/uploads/$favicon->id/$favicon->file_name", "/qcommerce/branding/favicon/$favicon->file_name");
                    } catch (Exception $exception) {

                    }
                    foreach (\Qubiqx\QcommerceCore\Classes\Sites::getSites() as $site) {
                        Customsetting::set('site_favicon', "/qcommerce/branding/favicon/$favicon->file_name", $site['id']);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('new_customsetting', function (Blueprint $table) {
            //
        });
    }
}
