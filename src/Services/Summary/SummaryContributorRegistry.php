<?php

namespace Dashed\DashedCore\Services\Summary;

use Throwable;
use Dashed\DashedCore\Services\Summary\Contracts\SummaryContributorInterface;

class SummaryContributorRegistry
{
    /** @var array<string, class-string<SummaryContributorInterface>>|null */
    protected static ?array $override = null;

    /** @return array<string, class-string<SummaryContributorInterface>> */
    public static function map(): array
    {
        if (static::$override !== null) {
            return static::$override;
        }

        $registered = function_exists('cms') ? (cms()->builder('summaryContributors', null) ?? []) : [];
        if (! is_array($registered)) {
            return [];
        }

        $map = [];
        foreach ($registered as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, SummaryContributorInterface::class)) {
                continue;
            }

            try {
                /** @var class-string<SummaryContributorInterface> $class */
                $map[$class::key()] = $class;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $map;
    }

    /**
     * Alleen voor tests: forceer (of wis met null) de contributor-map.
     *
     * @param  array<string, class-string<SummaryContributorInterface>>|null  $map
     */
    public static function fakeMap(?array $map): void
    {
        static::$override = $map;
    }
}
