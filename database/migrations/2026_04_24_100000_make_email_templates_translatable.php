<?php

use Dashed\DashedCore\Database\Migrations\WrapEmailTemplateTranslations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('dashed__email_templates', function (Blueprint $table) {
            $table->longText('subject')->nullable()->change();
            $table->longText('from_name')->nullable()->change();
        });

        WrapEmailTemplateTranslations::wrapExisting(config('app.fallback_locale', 'nl'));
    }

    public function down(): void
    {
        throw new \RuntimeException('Deze migratie is niet omkeerbaar: JSON-wrapping kan niet veilig terug naar single-locale.');
    }
};
