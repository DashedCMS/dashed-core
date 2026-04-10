<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Models\EmailTemplate;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->ensureRegisteredMailablesHaveTemplates();
    }

    protected function ensureRegisteredMailablesHaveTemplates(): void
    {
        foreach (cms()->emailTemplateRegistry()->all() as $mailableClass) {
            EmailTemplate::firstOrCreate(
                ['mailable_key' => $mailableClass::emailTemplateKey()],
                [
                    'name' => $mailableClass::emailTemplateName(),
                    'subject' => null,
                    'blocks' => [],
                    'is_active' => true,
                ]
            );
        }
    }
}
