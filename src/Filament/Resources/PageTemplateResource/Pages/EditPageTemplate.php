<?php

namespace Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Classes\Editor\TemplateBlocks;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource;

class EditPageTemplate extends EditRecord
{
    protected static string $resource = PageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['content'] = TemplateBlocks::sanitize($data['content'] ?? []);

        return $data;
    }
}
