<?php

namespace Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Classes\Locales;
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
        $mainLocale = config('app.locale') ?: (config('app.fallback_locale') ?: 'en');

        foreach (cms()->emailTemplateRegistry()->all() as $mailableClass) {
            $template = EmailTemplate::firstOrCreate(
                ['mailable_key' => $mailableClass::emailTemplateKey()],
                [
                    'name' => $mailableClass::emailTemplateName(),
                    'from_email' => method_exists($mailableClass, 'defaultFromEmail')
                        ? $mailableClass::defaultFromEmail()
                        : Customsetting::get('site_from_email'),
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

                $template->setTranslations('from_name', [$mainLocale => method_exists($mailableClass, 'defaultFromName')
                    ? $mailableClass::defaultFromName()
                    : Customsetting::get('site_name')]);

                if (method_exists($mailableClass, 'defaultBlocks')) {
                    $template->setTranslations('blocks', [$mainLocale => $mailableClass::defaultBlocks()]);
                }

                $template->save();
            }
        }
    }
}
