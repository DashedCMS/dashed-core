<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function parseJson(string $jsonString, int $depth = 512): mixed
    {
        return json_decode($jsonString, false, $depth, JSON_THROW_ON_ERROR) && is_array(json_decode($jsonString, true));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dashed__custom_settings', function (Blueprint $table) {
            $table->json('json')
                ->nullable();
        });
        //        foreach (\Dashed\DashedCore\Models\Customsetting::whereNotNull('json')->get() as $customsetting) {
        //            $customsetting->value = $customsetting->json;
        //            $customsetting->json = null;
        //            $customsetting->save();
        //        }

        foreach (\Dashed\DashedCore\Models\Customsetting::whereNotNull('value')->get() as $customsetting) {
            try {
                if (! is_int($customsetting->value)) {
                    $isJson = self::parseJson($customsetting->value);
                    if ($isJson) {
                        $customsetting->json = json_decode($customsetting->value, true);
                        $customsetting->value = null;
                        $customsetting->save();
                    }
                }
            } catch (\JsonException $exception) {
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
        Schema::dropIfExists('metadata');
    }
};
