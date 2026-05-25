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
            $tableName = $class->getTable();

            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'public')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('public')
                    ->default(1)
                    ->after('end_date');
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
