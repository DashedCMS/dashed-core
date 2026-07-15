<?php

namespace Dashed\DashedCore\Classes\Caching;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;

class CloudflareConfig
{
    private function __construct(
        private readonly ?string $apiToken,
        private readonly ?string $zoneId,
    ) {}

    public static function for(?string $siteId = null): self
    {
        $siteId = $siteId ?? Sites::getActive();

        return new self(
            apiToken: Customsetting::get('cloudflare_api_token', $siteId) ?: null,
            zoneId: Customsetting::get('cloudflare_zone_id', $siteId) ?: null,
        );
    }

    public function apiToken(): ?string
    {
        return $this->apiToken;
    }

    public function zoneId(): ?string
    {
        return $this->zoneId;
    }

    public function configured(): bool
    {
        return filled($this->apiToken) && filled($this->zoneId);
    }
}
