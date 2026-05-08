<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Dashed\DashedCore\Models\User;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Services\Summary\DTOs\SummaryPeriod;
use Dashed\DashedCore\Services\Summary\DTOs\SummarySection;

/**
 * Mailable voor de admin samenvatting-mail. Bouwt de unified
 * dashed-core::emails.layout op met per sectie een heading-block,
 * gevolgd door de blocks van de sectie en daarna een divider.
 *
 * De array $sections bevat SummarySection-DTOs. De build()-methode
 * sorteert ze alfabetisch op title zodat de layout consistent is en
 * de volgorde niet afhangt van de DB-ordering of contributor-registry.
 */
class SummaryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, SummarySection>  $sections
     */
    public function __construct(
        public User $user,
        public array $sections,
        public SummaryPeriod $period,
    ) {
    }

    public function build(): self
    {
        $siteName = (string) (Customsetting::get('site_name') ?: config('app.name', 'Site'));
        $siteUrl = (string) (Customsetting::get('site_url') ?: config('app.url'));

        $primaryColor = Customsetting::get('mail_primary_color') ?: '#A0131C';
        $textColor = Customsetting::get('mail_text_color', null, '#ffffff');
        $backgroundColor = Customsetting::get('mail_background_color', null, '#f3f4f6');
        $footerText = Customsetting::get('mail_footer_text');

        $showLogo = (bool) Customsetting::get('mail_show_logo', null, 1);
        $showSiteName = (bool) Customsetting::get('mail_show_site_name', null, 1);

        $siteLogo = null;
        if ($showLogo && function_exists('mediaHelper')) {
            $logoId = Customsetting::get('mail_logo') ?: Customsetting::get('site_logo');
            if ($logoId) {
                $media = mediaHelper()->getSingleMedia($logoId);
                $siteLogo = $media->url ?? null;
            }
        }

        $context = [
            'primaryColor' => $primaryColor,
            'textColor' => $textColor,
            'siteName' => $siteName,
        ];

        // Sorteer secties stabiel op title voor consistente layout.
        $sections = $this->sections;
        usort($sections, fn (SummarySection $a, SummarySection $b) => strcmp($a->title, $b->title));

        $registry = cms()->emailBlocks();
        $renderedBlocks = [];

        foreach ($sections as $section) {
            // Heading-block voor de sectie-titel.
            if (isset($registry['heading'])) {
                $renderedBlocks[] = $registry['heading']::render(
                    ['text' => $section->title, 'level' => 'h2'],
                    $context,
                );
            }

            foreach ($section->blocks as $block) {
                if (! is_array($block)) {
                    continue;
                }
                $type = $block['type'] ?? null;
                $data = is_array($block['data'] ?? null) ? $block['data'] : [];

                // Compatibiliteit: 'paragraph' is een alias voor het 'text'-block.
                if ($type === 'paragraph') {
                    $type = 'text';
                    if (isset($data['content']) && ! isset($data['body'])) {
                        $data['body'] = $data['content'];
                    }
                }

                if ($type === 'heading' && isset($data['content']) && ! isset($data['text'])) {
                    $data['text'] = $data['content'];
                }

                if (! $type || ! isset($registry[$type])) {
                    continue;
                }

                $renderedBlocks[] = $registry[$type]::render($data, $context);
            }

            if (isset($registry['divider'])) {
                $renderedBlocks[] = $registry['divider']::render([], $context);
            }
        }

        $subject = strtr('Samenvatting :siteName: - :periodLabel:', [
            ':siteName:' => $siteName,
            ':periodLabel:' => $this->period->label,
        ]);

        $fromEmail = Customsetting::get('site_from_email') ?: null;

        $mail = $this
            ->subject($subject)
            ->view('dashed-core::emails.layout')
            ->with([
                'blocks' => $renderedBlocks,
                'siteName' => $siteName,
                'siteLogo' => $siteLogo,
                'siteUrl' => $siteUrl,
                'showSiteName' => $showSiteName,
                'primaryColor' => $primaryColor,
                'textColor' => $textColor,
                'backgroundColor' => $backgroundColor,
                'footerText' => $footerText,
            ]);

        if ($fromEmail) {
            $mail->from($fromEmail, $siteName);
        }

        return $mail;
    }
}
