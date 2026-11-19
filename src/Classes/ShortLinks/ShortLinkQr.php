<?php

namespace Dashed\DashedCore\Classes\ShortLinks;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR-code van een korte link als SVG, lokaal gemaakt met chillerlan/php-qrcode
 * (komt mee met Filament voor de MFA-app). Geen externe dienst: een link naar
 * een QR-API zou elke korte link bij een derde partij neerleggen.
 */
class ShortLinkQr
{
    public static function available(): bool
    {
        return class_exists(QRCode::class) && class_exists(QROptions::class);
    }

    public static function svg(string $url): ?string
    {
        if (! self::available()) {
            return null;
        }

        return rescue(function () use ($url): string {
            $options = new QROptions([
                'outputType' => 'svg',
                'outputBase64' => false,
                'eccLevel' => 0b01, // M: leesbaar ook als een hoek van een poster beschadigd is
                'addQuietzone' => true,
                'svgAddXmlHeader' => false,
            ]);

            return (new QRCode($options))->render($url);
        }, null);
    }
}
