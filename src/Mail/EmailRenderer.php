<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;
use Dashed\DashedCore\Mail\Exceptions\EmptyEmailTemplateException;

class EmailRenderer
{
    public function render(EmailTemplate $template, array $context = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->withLocale($locale, function () use ($template, $context, $locale) {
            $blocks = $template->getTranslation('blocks', $locale, useFallbackLocale: true) ?? [];
            $subject = $template->getTranslation('subject', $locale, useFallbackLocale: true);

            if (empty($blocks) && blank($subject)) {
                throw new EmptyEmailTemplateException(
                    "EmailTemplate is empty for locale {$locale} and no fallback available",
                    ['key' => $template->mailable_key, 'locale' => $locale]
                );
            }

            $translationPrimaryColor = class_exists(\Dashed\DashedTranslations\Models\Translation::class)
                ? \Dashed\DashedTranslations\Models\Translation::get('primary-color-code', 'emails', '#A0131C')
                : '#A0131C';

            $primaryColor = Customsetting::get('mail_primary_color') ?: $translationPrimaryColor;
            $textColor = Customsetting::get('mail_text_color', null, '#ffffff');
            $backgroundColor = Customsetting::get('mail_background_color', null, '#f3f4f6');
            $footerText = Customsetting::get('mail_footer_text');
            $siteName = Customsetting::get('site_name');

            $context = array_merge([
                'primaryColor' => $primaryColor,
                'textColor' => $textColor,
                'siteName' => $siteName,
            ], $context);

            $blockRegistry = cms()->emailBlocks();
            $renderedBlocks = [];

            foreach ($blocks as $block) {
                $type = $block['type'] ?? null;
                $data = $block['data'] ?? [];

                if (! $type || ! isset($blockRegistry[$type])) {
                    continue;
                }

                $class = $blockRegistry[$type];
                $renderedBlocks[] = $class::render($data, $context);
            }

            $showLogo = (bool) Customsetting::get('mail_show_logo', null, 1);
            $showSiteName = (bool) Customsetting::get('mail_show_site_name', null, 1);

            $siteLogo = null;
            if ($showLogo) {
                $logoId = Customsetting::get('mail_logo') ?: Customsetting::get('site_logo');
                $siteLogo = $logoId ? (mediaHelper()->getSingleMedia($logoId)->url ?? '') : null;
            }

            $siteUrl = Customsetting::get('site_url') ?: config('app.url');

            return view('dashed-core::emails.layout', [
                'blocks' => $renderedBlocks,
                'siteName' => $siteName,
                'siteLogo' => $siteLogo,
                'siteUrl' => $siteUrl,
                'showSiteName' => $showSiteName,
                'primaryColor' => $primaryColor,
                'textColor' => $textColor,
                'backgroundColor' => $backgroundColor,
                'footerText' => $footerText,
            ])->render();
        });
    }

    public function renderSubject(EmailTemplate $template, array $context = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $subject = $template->getTranslation('subject', $locale, useFallbackLocale: true);

        return preg_replace_callback(
            '/:(\w+):/',
            fn ($m) => array_key_exists($m[1], $context) ? (string) $context[$m[1]] : $m[0],
            (string) $subject
        );
    }

    /**
     * Temporarily switch the application locale for the duration of the callback,
     * then restore the previous locale. Mirrors Laravel's Localizable::withLocale
     * semantics but routed through the App facade so translation reads (__()/trans())
     * inside block renderers pick up the requested locale.
     */
    private function withLocale(string $locale, \Closure $callback): mixed
    {
        $previous = App::getLocale();

        try {
            App::setLocale($locale);

            return $callback();
        } finally {
            App::setLocale($previous);
        }
    }
}
