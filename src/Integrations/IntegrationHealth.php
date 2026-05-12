<?php

namespace Dashed\DashedCore\Integrations;

use Carbon\CarbonInterface;
use Dashed\DashedCore\Enums\IntegrationStatus;

/**
 * Result of a single health-check probe for an integration. Carries the
 * status enum, an optional human-readable message, and the timestamp of
 * the most recent successful contact (persisted to Customsetting between
 * probes so the dashboard can show "last seen X ago" even when the latest
 * probe failed).
 */
final readonly class IntegrationHealth
{
    public function __construct(
        public IntegrationStatus $status,
        public ?string $message = null,
        public ?CarbonInterface $lastSuccessAt = null,
    ) {
    }

    public static function ok(?CarbonInterface $at = null): self
    {
        return new self(IntegrationStatus::Connected, lastSuccessAt: $at);
    }

    public static function misconfigured(string $msg): self
    {
        return new self(IntegrationStatus::Misconfigured, $msg);
    }

    public static function failing(string $msg, ?CarbonInterface $lastSuccessAt = null): self
    {
        return new self(IntegrationStatus::Failing, $msg, $lastSuccessAt);
    }

    public static function disabled(): self
    {
        return new self(IntegrationStatus::Disabled);
    }
}
