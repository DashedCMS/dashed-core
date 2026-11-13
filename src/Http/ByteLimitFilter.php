<?php

namespace Dashed\DashedCore\Http;

use php_user_filter;

/**
 * Schrijffilter op het tijdelijke bestand van SafeDownloader. Guzzle schrijft
 * de (al gedecodeerde) body in stukken naar de sink; dit filter telt mee en
 * laat de schrijfactie mislukken zodra de grens gepasseerd is, waarna curl de
 * overdracht afbreekt. Een eigen CURLOPT_WRITEFUNCTION kan niet: Guzzle
 * beheert die zelf voor de sink.
 *
 * @internal
 */
final class ByteLimitFilter extends php_user_filter
{
    public const NAME = 'dashed.byte-limit';

    public static function register(): void
    {
        if (! in_array(self::NAME, stream_get_filters(), true)) {
            stream_filter_register(self::NAME, self::class);
        }
    }

    public function filter($in, $out, &$consumed, bool $closing): int
    {
        $state = $this->params;

        while ($bucket = stream_bucket_make_writeable($in)) {
            $state->received += $bucket->datalen;

            if ($state->received > $state->max) {
                $state->exceeded = true;

                return PSFS_ERR_FATAL;
            }

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
