<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    public static function createTableForVisitableModel($tableName)
    {
        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('content')
                ->nullable();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained($tableName);
            $table->json('site_ids')
                ->nullable();
            $table->dateTime('start_date')
                ->nullable();
            $table->dateTime('end_date')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
