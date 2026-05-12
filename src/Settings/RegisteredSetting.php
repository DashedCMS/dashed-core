<?php

namespace Dashed\DashedCore\Settings;

use DateTimeImmutable;

/**
 * Immutable description of a Customsetting key in the registry.
 *
 * - `explicit = true`  → registered by a service provider via `cms()->registerSetting(...)`.
 * - `explicit = false` → auto-registered on first `Customsetting::get($key, ...)` call.
 */
class RegisteredSetting
{
    public readonly DateTimeImmutable $firstSeenAt;

    public function __construct(
        public readonly string $key,
        public readonly string $type,
        public readonly mixed $default,
        public readonly string $package,
        public readonly ?string $label,
        public readonly ?string $description,
        public readonly bool $explicit,
        public readonly ?string $caller = null,
        ?DateTimeImmutable $firstSeenAt = null,
    ) {
        $this->firstSeenAt = $firstSeenAt ?? new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'default' => $this->default,
            'package' => $this->package,
            'label' => $this->label,
            'description' => $this->description,
            'explicit' => $this->explicit,
            'caller' => $this->caller,
            'firstSeenAt' => $this->firstSeenAt->format(DATE_ATOM),
        ];
    }
}
