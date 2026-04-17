<?php

namespace Dashed\DashedCore\Mail;

use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\EmailTemplate;

class EmailRenderer
{
    public function render(EmailTemplate $template, array $context): string
    {
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

        foreach ($template->blocks ?? [] as $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? [];

            if (! $type || ! isset($blockRegistry[$type])) {
                continue;
            }

            $class = $blockRegistry[$type];
            $renderedBlocks[] = $class::render($data, $context);
        }

        $logoId = Customsetting::get('mail_logo') ?: Customsetting::get('site_logo');
        $siteLogo = $logoId ? (mediaHelper()->getSingleMedia($logoId)->url ?? '') : null;

        return view('dashed-core::emails.layout', [
            'blocks' => $renderedBlocks,
            'siteName' => $siteName,
            'siteLogo' => $siteLogo,
            'primaryColor' => $primaryColor,
            'textColor' => $textColor,
            'backgroundColor' => $backgroundColor,
            'footerText' => $footerText,
        ])->render();
    }

    public function renderSubject(EmailTemplate $template, array $context): string
    {
        return preg_replace_callback(
            '/:(\w+):/',
            fn ($m) => array_key_exists($m[1], $context) ? (string) $context[$m[1]] : $m[0],
            (string) $template->subject
        );
    }
}
