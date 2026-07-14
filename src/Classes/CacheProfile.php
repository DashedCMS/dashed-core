<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\Customsetting;

class CacheProfile
{
    public const B2C = 'b2c';
    public const B2B = 'b2b';
    public const MIXED = 'mixed';
    public const OFF = 'off';

    private const PRESETS = [
        'b2c'   => ['edge' => true,  'response' => true,  'bypassLogin' => true, 'bypassPriceGroups' => true, 'ttl' => 300],
        'b2b'   => ['edge' => false, 'response' => false, 'bypassLogin' => true, 'bypassPriceGroups' => true, 'ttl' => 300],
        'mixed' => ['edge' => true,  'response' => true,  'bypassLogin' => true, 'bypassPriceGroups' => true, 'ttl' => 300],
        'off'   => ['edge' => false, 'response' => false, 'bypassLogin' => true, 'bypassPriceGroups' => true, 'ttl' => 0],
    ];

    private function __construct(
        private readonly string $name,
        private readonly array $flags,
    ) {}

    public static function all(): array
    {
        return array_keys(self::PRESETS);
    }

    public static function fromName(?string $name): self
    {
        $name = array_key_exists($name, self::PRESETS) ? $name : self::MIXED;

        return new self($name, self::PRESETS[$name]);
    }

    public static function forSite(?string $siteId = null): self
    {
        $siteId = $siteId ?? Sites::getActive();

        return self::fromName(Customsetting::get('cache_profile', $siteId, self::MIXED));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function edgeEnabled(): bool
    {
        return $this->flags['edge'];
    }

    public function responseCache(): bool
    {
        return $this->flags['response'];
    }

    public function bypassWhenLoggedIn(): bool
    {
        return $this->flags['bypassLogin'];
    }

    public function bypassPriceGroups(): bool
    {
        return $this->flags['bypassPriceGroups'];
    }

    public function responseTtl(): int
    {
        return $this->flags['ttl'];
    }
}
