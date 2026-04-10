<?php

namespace Dashed\DashedCore\Mail\Concerns;

use Dashed\DashedCore\Mail\EmailRenderer;
use Dashed\DashedCore\Models\EmailTemplate;

trait HasEmailTemplate
{
    protected function renderFromTemplate(array $data): ?string
    {
        $template = EmailTemplate::forMailable(static::emailTemplateKey());
        if (! $template) {
            return null;
        }

        return app(EmailRenderer::class)->render($template, $data);
    }

    protected function templateSubject(string $fallback): string
    {
        $template = EmailTemplate::forMailable(static::emailTemplateKey());

        return $template?->subject ?: $fallback;
    }
}
