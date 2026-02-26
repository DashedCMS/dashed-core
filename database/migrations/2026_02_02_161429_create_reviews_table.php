<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dashed__reviews', function (Blueprint $table) {
            $table->id();

            $table->string('provider')
                ->default('own');
            $table->string('review_id')
                ->nullable();
            $table->string('name')
                ->nullable();
            $table->string('company')
                ->nullable();
            $table->string('image')
                ->nullable();
            $table->string('review')
                ->nullable();
            $table->integer('stars')
                ->nullable();
            $table->string('profile_image')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
    }
};
