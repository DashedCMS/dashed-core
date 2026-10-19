<?php

namespace Dashed\DashedCore\Classes\Seo;

class MetaImage
{
    /**
     * Een geplakte URL gaat ongewijzigd door; een media-id wordt een URL met
     * afmetingen als die bekend zijn. Zonder afmetingen rendert de head geen
     * og:image:width en og:image:height, in plaats van een vaste 1200x630.
     *
     * @return array{url: string, width: ?int, height: ?int}
     */
    public static function resolve(mixed $value): array
    {
        $none = ['url' => '', 'width' => null, 'height' => null];

        if (! is_string($value) && ! is_int($value)) {
            return $none;
        }

        $value = (string) $value;

        if ($value === '') {
            return $none;
        }

        if (! is_numeric($value)) {
            return ['url' => $value, 'width' => null, 'height' => null];
        }

        $media = mediaHelper()->getSingleMedia($value, 'original');

        if (! is_object($media) || empty($media->url)) {
            return $none;
        }

        return [
            'url' => (string) $media->url,
            'width' => isset($media->width) && is_numeric($media->width) ? (int) $media->width : null,
            'height' => isset($media->height) && is_numeric($media->height) ? (int) $media->height : null,
        ];
    }
}
