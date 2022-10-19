<?php

namespace Qubiqx\QcommerceCore;

use Qubiqx\QcommerceCore\Models\Customsetting;

class SeoManager
{
    public function __construct()
    {
        self::metaData('metaImage', app(\Flowframe\Drift\UrlBuilder::class)->url('qcommerce', Customsetting::get('default_meta_data_image'), []));
    }

    protected static $metaData = [
        'metaTitle' => '',
        'metaDescription' => '',
        'metaImage' => '',
        'ogType' => 'website',
        'twitterSite' => '',
        'twitterCreator' => '',
        'webmasterTags' => [],
        'robots' => 'index, follow',
        'schemas' => [],
        'alternateUrls' => [],
    ];

    public function metaData(string $name, string|array $value = null): self|string|array
    {
        if (! $value) {
            return static::$metaData[$name] ?? '';
        }

        static::$metaData[$name] = $value;

        return $this;
    }
}
