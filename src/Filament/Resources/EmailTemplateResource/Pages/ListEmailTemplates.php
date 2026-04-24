<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListEmailTemplates extends ListRecords
{
    use Translatable;

    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

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
                    'subject' => method_exists($mailableClass, 'defaultSubject')
                        ? $mailableClass::defaultSubject()
                        : null,
                    'from_name' => method_exists($mailableClass, 'defaultFromName')
                        ? $mailableClass::defaultFromName()
                        : Customsetting::get('site_name'),
                    'from_email' => method_exists($mailableClass, 'defaultFromEmail')
                        ? $mailableClass::defaultFromEmail()
                        : Customsetting::get('site_from_email'),
                    'blocks' => method_exists($mailableClass, 'defaultBlocks')
                        ? $mailableClass::defaultBlocks()
                        : [],
                    'is_active' => true,
                ]
            );
        }
    }
}
