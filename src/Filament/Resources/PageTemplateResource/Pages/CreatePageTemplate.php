<?php

namespace Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Classes\Editor\TemplateBlocks;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource;

class CreatePageTemplate extends CreateRecord
{
    protected static string $resource = PageTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['content'] = TemplateBlocks::sanitize($data['content'] ?? []);
        $data['created_by'] = auth()->id();

        return $data;
    }
}
