<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Paginasjablonen: de blokken van een pagina, zonder pagina-eigen
        // sleutels, om een nieuwe pagina mee te beginnen. Zie TemplateBlocks.
        if (! Schema::hasTable('dashed__page_templates')) {
            Schema::create('dashed__page_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('model_type')->nullable()->index();
                $table->json('content')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashed__page_templates');
    }
};
