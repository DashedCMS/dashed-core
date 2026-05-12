<?php

namespace Dashed\DashedCore\Settings;

/**
 * In-memory registry of every Customsetting key the app knows about,
 * with type, owning package, default, and explicit/auto provenance.
 *
 * Bound as a singleton by DashedCoreServiceProvider. Never persisted —
 * the registry rebuilds on every boot. That's intentional: explicit
 * entries are seeded by service-provider register() calls; auto entries
 * are seeded by Customsetting::get() touches at request-time.
 */
class SettingsRegistry
{
    /** @var array<string, RegisteredSetting> */
    protected array $settings = [];

    /**
     * Explicit registration from a service provider.
     * Always wins over a prior auto entry.
     */
    public function register(
        string $key,
        string $type,
        mixed $default,
        string $package,
        ?string $label,
        ?string $description = null,
    ): RegisteredSetting {
        $entry = new RegisteredSetting(
            key: $key,
            type: $type,
            default: $default,
            package: $package,
            label: $label,
            description: $description,
            explicit: true,
        );
        $this->settings[$key] = $entry;
        return $entry;
    }

    /**
     * First-touch auto-registration from Customsetting::get().
     * No-op if the key is already known (explicit or auto).
     * Atomic in PHP's single-threaded request: a re-check + assign
     * inside the same call avoids double-register on re-entry.
     */
    public function touch(string $key, mixed $default, ?string $caller): ?RegisteredSetting
    {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        $entry = new RegisteredSetting(
            key: $key,
            type: 'mixed',
            default: $default,
            package: 'unknown',
            label: null,
            description: null,
            explicit: false,
            caller: $caller,
        );
        $this->settings[$key] = $entry;
        return $entry;
    }

    public function get(string $key): ?RegisteredSetting
    {
        return $this->settings[$key] ?? null;
    }

    public function isExplicit(string $key): bool
    {
        return isset($this->settings[$key]) && $this->settings[$key]->explicit;
    }

    /** @return array<string, RegisteredSetting> */
    public function all(): array
    {
        return $this->settings;
    }
}
