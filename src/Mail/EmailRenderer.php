<?php

namespace Dashed\DashedCore\Mail;

use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;

class EmailRenderer
{
    public function render(EmailTemplate $template, array $context): string
    {
        $blockRegistry = cms()->emailBlocks();
        $renderedBlocks = [];

        foreach ($template->blocks ?? [] as $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? [];

            if (! $type || ! isset($blockRegistry[$type])) {
                continue;
            }

            $class = $blockRegistry[$type];
            $renderedBlocks[] = $class::render($data, $context);
        }

        return view('dashed-core::emails.layout', [
            'blocks' => $renderedBlocks,
            'siteName' => Customsetting::get('site_name'),
            'siteLogo' => Customsetting::get('site_logo'),
        ])->render();
    }

    public function renderSubject(EmailTemplate $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn ($m) => (string) ($context[$m[1]] ?? ''),
            (string) $template->subject
        );
    }
}
