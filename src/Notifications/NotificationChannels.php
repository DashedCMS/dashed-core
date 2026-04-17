<?php

namespace Dashed\DashedCore\Notifications;

class NotificationChannels
{
    /**
     * @var array<string, array{key: string, label: string}>
     */
    private static array $registered = [];

    public static function register(string $key, string $label): void
    {
        self::$registered[$key] = [
            'key' => $key,
            'label' => $label,
        ];
    }

    /**
     * @return array<string, array{key: string, label: string}>
     */
    public static function all(): array
    {
        return self::$registered;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::$registered);
    }

    public static function has(string $key): bool
    {
        return isset(self::$registered[$key]);
    }

    public static function labelFor(string $key): string
    {
        return self::$registered[$key]['label'] ?? $key;
    }
}
