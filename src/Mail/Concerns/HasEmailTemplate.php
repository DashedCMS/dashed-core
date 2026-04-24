<?php

namespace Dashed\DashedCore\Mail\Concerns;

use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;

trait HasEmailTemplate
{
    public static function emailTemplateKey(): string
    {
        return static::class;
    }

    public static function emailTemplateDescription(): ?string
    {
        return null;
    }

    public static function defaultFromName(): ?string
    {
        return Customsetting::get('site_name');
    }

    public static function defaultFromEmail(): ?string
    {
        return Customsetting::get('site_from_email');
    }

    public static function availableBlockKeys(): array
    {
        return array_keys(cms()->emailBlocks());
    }

    public static function availableVariables(): array
    {
        return ['siteName', 'primaryColor'];
    }

    protected function renderFromTemplate(array $data, ?string $locale = null): ?string
    {
        $template = EmailTemplate::forMailable(static::emailTemplateKey());
        if (! $template) {
            return null;
        }

        return app(EmailRenderer::class)->render($template, $data, $locale);
    }

    protected function templateSubject(string $fallback, array $context = [], ?string $locale = null): string
    {
        $template = EmailTemplate::forMailable(static::emailTemplateKey());

        if (! $template) {
            return $fallback;
        }

        $locale ??= app()->getLocale();
        $subjectTranslation = $template->getTranslation('subject', $locale, useFallbackLocale: true);
        if (blank($subjectTranslation)) {
            return $fallback;
        }

        return app(EmailRenderer::class)->renderSubject($template, $context, $locale);
    }

    /**
     * @return array{0: string|null, 1: string|null} [email, name]
     */
    protected function templateFrom(?string $fallbackEmail, ?string $fallbackName, ?string $locale = null): array
    {
        $template = EmailTemplate::forMailable(static::emailTemplateKey());

        if (! $template) {
            return [$fallbackEmail, $fallbackName];
        }

        $locale ??= app()->getLocale();
        $fromName = $template->getTranslation('from_name', $locale, useFallbackLocale: true);

        return [
            $template->from_email ?: $fallbackEmail,
            filled($fromName) ? $fromName : $fallbackName,
        ];
    }
}
