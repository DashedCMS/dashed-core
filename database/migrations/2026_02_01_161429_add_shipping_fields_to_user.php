<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

// Contact
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }

            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }

            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 10)->nullable();
            }

            if (!Schema::hasColumn('users', 'marketing')) {
                $table->boolean('marketing')->default(false);
            }

// Shipping address
            if (!Schema::hasColumn('users', 'street')) {
                $table->string('street')->nullable();
            }

            if (!Schema::hasColumn('users', 'house_nr')) {
                $table->string('house_nr')->nullable();
            }

            if (!Schema::hasColumn('users', 'zip_code')) {
                $table->string('zip_code')->nullable();
            }

            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }

            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }

// Company
            if (!Schema::hasColumn('users', 'is_company')) {
                $table->boolean('is_company')->default(false);
            }

            if (!Schema::hasColumn('users', 'company')) {
                $table->string('company')->nullable();
            }

            if (!Schema::hasColumn('users', 'tax_id')) {
                $table->string('tax_id')->nullable();
            }

// Invoice address
            if (!Schema::hasColumn('users', 'invoice_street')) {
                $table->string('invoice_street')->nullable();
            }

            if (!Schema::hasColumn('users', 'invoice_house_nr')) {
                $table->string('invoice_house_nr')->nullable();
            }

            if (!Schema::hasColumn('users', 'invoice_zip_code')) {
                $table->string('invoice_zip_code')->nullable();
            }

            if (!Schema::hasColumn('users', 'invoice_city')) {
                $table->string('invoice_city')->nullable();
            }

            if (!Schema::hasColumn('users', 'invoice_country')) {
                $table->string('invoice_country')->nullable();
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $columns = [
                'phone_number',
                'date_of_birth',
                'gender',
                'marketing',
                'street',
                'house_nr',
                'zip_code',
                'city',
                'country',
                'is_company',
                'company',
                'tax_id',
                'invoice_street',
                'invoice_house_nr',
                'invoice_zip_code',
                'invoice_city',
                'invoice_country',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
