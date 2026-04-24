<?php

namespace Dashed\DashedCore\Mail\Concerns;

use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;

/**
 * Voor locale-aware mails met order/customer-context:
 *
 *     use HasEmailTemplate, ResolvesEmailLocale;
 *
 *     public function content(): Content
 *     {
 *         $locale = $this->resolveLocale($this->order, $this->order?->customer);
 *
 *         return new Content(
 *             htmlString: $this->renderFromTemplate(['order' => $this->order], $locale),
 *         );
 *     }
 *
 *     public function envelope(): Envelope
 *     {
 *         $locale = $this->resolveLocale($this->order, $this->order?->customer);
 *
 *         [$fromEmail, $fromName] = $this->templateFrom(
 *             Customsetting::get('site_from_email'),
 *             Customsetting::get('site_name'),
 *             $locale,
 *         );
 *
 *         return new Envelope(
 *             from: new Address($fromEmail, $fromName),
 *             subject: $this->templateSubject('Fallback', ['order' => $this->order], $locale),
 *         );
 *     }
 *
 * Mails zonder order/customer-context mogen $locale weglaten: de trait valt
 * dan terug op app()->getLocale() (zie NotificationMail als referentie).
 */
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
