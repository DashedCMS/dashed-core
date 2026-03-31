<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand users.role enum to include superadmin
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'customer') NOT NULL DEFAULT 'customer'");

        // Rename all existing admins to superadmin
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('dashed__model_has_roles')->whereIn(
            'role_id',
            DB::table('dashed__roles')->where('name', 'superadmin')->pluck('id')
        )->delete();

        DB::table('dashed__roles')->where('name', 'superadmin')->delete();

        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer'");
    }
};
