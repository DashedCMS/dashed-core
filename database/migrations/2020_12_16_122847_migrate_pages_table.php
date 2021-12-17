<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

class MigratePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (\Qubiqx\QcommerceCore\Models\Page::get() as $page) {
            $newContent = [];
            foreach (\Qubiqx\QcommerceCore\Classes\Locales::getLocales() as $locale) {
                $newBlocks = [];
                foreach (json_decode($page->getTranslation('content', $locale['id']), true) as $block) {
                    $newBlocks[Str::orderedUuid()->toString()] = [
                        'type' => $block['type'],
                        'data' => $block['data'],
                    ];
                }
                $newContent[$locale['id']] = $newBlocks;
            }
            $page->content = $newContent;
            $page->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}
