<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Dashed\DashedCore\Classes\Locales;
use Filament\Resources\Pages\ListRecords;
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
        $mainLocale = config('app.locale') ?: (config('app.fallback_locale') ?: 'en');

        foreach (cms()->emailTemplateRegistry()->all() as $mailableClass) {
            // from_name en from_email worden bewust NIET voorgevuld: leeg
            // betekent "gebruik site_name / site_from_email uit de algemene
            // instellingen" (zie HasEmailTemplate::templateFrom()). Zou je ze
            // hier met de op dat moment geldende instellingen vullen, dan
            // bevriest de afzender en heeft een latere wijziging in de
            // algemene instellingen geen effect meer.
            $template = EmailTemplate::firstOrCreate(
                ['mailable_key' => $mailableClass::emailTemplateKey()],
                [
                    'name' => $mailableClass::emailTemplateName(),
                    'is_active' => true,
                ]
            );

            // Seed default content under the fallback locale, but only when the
            // template has no filled locale yet (a fresh record, or one created
            // by older code that stored translatable defaults incorrectly:
            // assigning a plain array to a translatable attribute makes Spatie
            // treat its keys as locales). Never overwrite content an editor has
            // already filled in any locale.
            $hasAnyContent = collect(Locales::getLocales())
                ->contains(fn ($locale) => $template->hasLocaleFilled($locale['id']));

            if (! $hasAnyContent) {
                if (method_exists($mailableClass, 'defaultSubject')) {
                    $template->setTranslations('subject', [$mainLocale => $mailableClass::defaultSubject()]);
                }

                if (method_exists($mailableClass, 'defaultBlocks')) {
                    $template->setTranslations('blocks', [$mainLocale => $mailableClass::defaultBlocks()]);
                }

                $template->save();
            }
        }
    }
}
