<?php

namespace Dashed\DashedCore\Mail;

use Dashed\DashedCore\Models\Customsetting;

/**
 * De variabelen die dashed-core::emails.layout nodig heeft (sitenaam, logo,
 * kleuren, voettekst), gelezen uit Instellingen, E-mail van één site. Dezelfde
 * tien regels stonden in EmailRenderer, SummaryMail, OrderHandledMail en de
 * twee retourlabelmails; nieuwe mails halen ze hier.
 */
class MailBranding
{
    public const DEFAULT_PRIMARY_COLOR = '#A0131C';

    /**
     * @return array{siteName: string, siteLogo: ?string, siteUrl: string, showSiteName: bool, primaryColor: string, textColor: string, backgroundColor: string, footerText: ?string}
     */
    public static function for(?string $siteId = null): array
    {
        $siteLogo = null;
        if ((bool) Customsetting::get('mail_show_logo', $siteId, 1)) {
            $logoId = Customsetting::get('mail_logo', $siteId) ?: Customsetting::get('site_logo', $siteId);
            if ($logoId) {
                $siteLogo = rescue(fn () => mediaHelper()->getSingleMedia($logoId)->url ?? null, null, false);
            }
        }

        return [
            'siteName' => (string) (Customsetting::get('site_name', $siteId) ?: config('app.name', '')),
            'siteLogo' => $siteLogo ?: null,
            'siteUrl' => (string) (Customsetting::get('site_url', $siteId) ?: config('app.url', '')),
            'showSiteName' => (bool) Customsetting::get('mail_show_site_name', $siteId, 1),
            'primaryColor' => (string) (Customsetting::get('mail_primary_color', $siteId) ?: self::DEFAULT_PRIMARY_COLOR),
            'textColor' => (string) (Customsetting::get('mail_text_color', $siteId) ?: '#ffffff'),
            'backgroundColor' => (string) (Customsetting::get('mail_background_color', $siteId) ?: '#f3f4f6'),
            'footerText' => Customsetting::get('mail_footer_text', $siteId) ?: null,
        ];
    }
}
