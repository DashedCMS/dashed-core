<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashed__roles', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->json('extra_permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('dashed__model_has_roles', static function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('role_id')->references('id')->on('dashed__roles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__model_has_roles');
        Schema::dropIfExists('dashed__roles');
    }
};
