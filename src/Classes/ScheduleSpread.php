<?php

namespace Dashed\DashedCore\Classes;

use InvalidArgumentException;

/**
 * Spreidt nacht- en uurtaken per site, zodat niet alle sites op een server in
 * dezelfde minuut starten. De verschuiving volgt uit app.url en de taaknaam,
 * dus elk proces van een site rekent dezelfde minuut uit.
 */
class ScheduleSpread
{
    public static function minute(string $task, int $window = 55): int
    {
        $window = max(1, min($window, 1440));

        return crc32(rtrim((string) config('app.url'), '/').'|'.$task) % $window;
    }

    /**
     * Blijft binnen het uur zolang de startminuut plus het venster dat toelaat.
     */
    public static function at(string $hhmm, string $task, int $window = 55): string
    {
        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $hhmm, $parts)) {
            throw new InvalidArgumentException("Ongeldige tijd: {$hhmm}");
        }

        $total = ((int) $parts[1] * 60 + (int) $parts[2] + self::minute($task, $window)) % 1440;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
