<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExtendUsersTable extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'first_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->enum('role', ['admin', 'customer'])->default('customer');
            });
        }
    }
}
