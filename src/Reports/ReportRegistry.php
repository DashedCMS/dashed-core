<?php

namespace Dashed\DashedCore\Reports;

/**
 * Alle aangemelde rapportbronnen, uit `cms()->builder('reportSources')`.
 */
class ReportRegistry
{
    /** @return array<string, ReportSource> */
    public static function all(): array
    {
        $sources = [];
        foreach ((array) cms()->builder('reportSources') as $class) {
            if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, ReportSource::class)) {
                continue;
            }
            $source = app($class);
            $sources[$source->key()] = $source;
        }

        return $sources;
    }

    public static function get(?string $key): ?ReportSource
    {
        return $key === null ? null : (self::all()[$key] ?? null);
    }

    /** @return array<string, string> */
    public static function options($user = null): array
    {
        $options = [];
        foreach (self::all() as $key => $source) {
            if ($user === null || $source->canBeUsedBy($user)) {
                $options[$key] = $source->label();
            }
        }

        return $options;
    }
}
