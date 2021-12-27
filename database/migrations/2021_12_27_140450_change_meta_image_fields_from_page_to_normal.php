<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMetaImageFieldsFromPageToNormal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qcommerce__pages', function (Blueprint $table) {
            $table->json('meta_image')->after('meta_description')->nullable();
        });

        foreach (\Qubiqx\QcommerceCore\Models\Page::get() as $page) {
            foreach (\Qubiqx\QcommerceCore\Classes\Locales::getLocales() as $locale) {
                $media = \Illuminate\Support\Facades\DB::table('media')
                    ->where('model_type', 'Qubiqx\Qcommerce\Models\Page')
                    ->where('model_id', $page->id)
                    ->where('collection_name', 'meta-image-' . $locale['id'])
                    ->first();

                if ($media) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists("/qcommerce/uploads/$media->id/$media->file_name")) {
                        try {
                            \Illuminate\Support\Facades\Storage::disk('public')->copy("/qcommerce/uploads/$media->id/$media->file_name", "/qcommerce/pages/meta-images/$media->file_name");
                        } catch (Exception $exception) {

                        }
                        $page->setTranslation('meta_image', $locale['id'], "/qcommerce/pages/meta-images/$media->file_name");
                        $page->save();
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
        Schema::table('page_to_normal', function (Blueprint $table) {
            //
        });
    }
}
