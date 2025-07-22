<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (cms()->builder('routeModels') as $routeModel) {
            $class = new $routeModel['class']();
            Schema::table($class->getTable(), function (Blueprint $table) use ($class) {
                if (! Schema::hasColumn($class->getTable(), 'public')) {
                    $table->boolean('public')
                        ->default(1)
                        ->after('end_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
