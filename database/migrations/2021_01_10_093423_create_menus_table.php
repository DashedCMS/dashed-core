<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qcommerce__menus', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('qcommerce__menu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('menu_id')->nullable()->constrained('qcommerce__menus');
            $table->foreignId('parent_menu_item_id')->nullable()->constrained('qcommerce__menu_items');
            $table->json('site_ids');
            $table->json('name');
            $table->json('url')->nullable();
            $table->string('type')->nullable();
            $table->string('model')->nullable();
            $table->integer('model_id')->nullable();
            $table->integer('order')->default(1);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
