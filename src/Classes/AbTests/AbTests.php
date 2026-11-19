<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Classes\AbTests;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Models\AbTest;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\AbTestStat;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\Consent\ConsentGate;
use Dashed\DashedCore\Models\AbTestConversion;

/**
 * A/B-tests op blokken: de verdeling gebeurt in de browser, zodat de
 * gecachete HTML voor iedereen gelijk blijft. De server rendert beide
 * varianten; het script kiest er één, onthoudt die in localStorage en meldt
 * vertoning, klik en de toewijzing via het cookie `dashed_ab`.
 */
class AbTests
{
    public const COOKIE = 'dashed_ab';

    public const EVENTS = ['view', 'click'];

    public static function enabled(?string $siteId = null): bool
    {
        $value = Customsetting::get('ab_tests_enabled', $siteId ?: Sites::getActive());

        return in_array($value, [true, 1, '1', 'true'], true);
    }

    /**
     * De lopende tests voor de blokken in deze content, in één query, en
     * alleen als er een blok met een test-id in staat.
     *
     * @param  array<int, mixed>  $content
     * @return Collection<int, AbTest>
     */
    public static function runningFor(mixed $content): Collection
    {
        if (! is_array($content)) {
            return collect();
        }

        $ids = [];
        foreach ($content as $block) {
            $id = is_array($block) ? ($block['data'][AbTest::BLOCK_KEY] ?? null) : null;
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if ($ids === []) {
            return collect();
        }

        try {
            if (! static::enabled()) {
                return collect();
            }

            return AbTest::query()
                ->whereIn('id', array_unique($ids))
                ->where('status', AbTest::STATUS_RUNNING)
                ->where('site_id', Sites::getActive())
                ->get()
                ->filter(fn (AbTest $test) => $test->variantBData() !== null)
                ->keyBy('id');
        } catch (Throwable) {
            // Een ontbrekende tabel midden in een uitrol mag de pagina niet breken.
            return collect();
        }
    }

    /** Eén teller ophogen voor vandaag, atomair. */
    public static function increment(AbTest $test, string $variant, string $column): void
    {
        $date = now()->toDateString();

        AbTestStat::query()->insertOrIgnore([
            'ab_test_id' => $test->id,
            'variant' => $variant,
            'date' => $date,
            'views' => 0,
            'clicks' => 0,
            'conversions' => 0,
        ]);

        AbTestStat::query()
            ->where('ab_test_id', $test->id)
            ->where('variant', $variant)
            ->where('date', $date)
            ->increment($column);
    }

    /**
     * De toewijzingen uit het cookie: test-id => variant. Het cookie wordt
     * door JavaScript gezet en is uitgezonderd van EncryptCookies.
     *
     * @return array<int, string>
     */
    public static function assignmentsFrom(?Request $request): array
    {
        $raw = (string) ($request?->cookies->get(self::COOKIE) ?? '');
        if ($raw === '' || strlen($raw) > 2000) {
            return [];
        }

        $assignments = [];
        foreach (explode('.', $raw) as $part) {
            if (preg_match('/^(\d{1,10})([ab])$/', $part, $m)) {
                $assignments[(int) $m[1]] = $m[2];
            }
        }

        return $assignments;
    }

    /**
     * Legt de toewijzingen uit het verzoek vast bij een bestelling of
     * inzending, voor de lopende tests met dit doel. Alleen met toestemming
     * voor analytics.
     */
    public static function recordGoal(string $goal, Model $subject, ?Request $request = null, bool $converted = true): int
    {
        $request ??= request();
        $assignments = static::assignmentsFrom($request);

        if ($assignments === [] || ! ConsentGate::allows('analytics')) {
            return 0;
        }

        $tests = AbTest::query()
            ->whereIn('id', array_keys($assignments))
            ->where('status', AbTest::STATUS_RUNNING)
            ->where('goal', $goal)
            ->get();

        $count = 0;
        foreach ($tests as $test) {
            $created = AbTestConversion::query()->insertOrIgnore([
                'ab_test_id' => $test->id,
                'variant' => $assignments[$test->id],
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'converted_at' => $converted ? now() : null,
                'created_at' => now(),
            ]);

            if ($created && $converted) {
                static::increment($test, $assignments[$test->id], 'conversions');
                $count++;
            }
        }

        return $count;
    }

    /**
     * Een eerder vastgelegde bestelling is betaald: telt nu als conversie,
     * één keer, ook als de betaling via een webhook binnenkomt.
     */
    public static function markConverted(Model $subject): int
    {
        $pending = AbTestConversion::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->whereNull('converted_at')
            ->get();

        $count = 0;
        foreach ($pending as $conversion) {
            $updated = AbTestConversion::query()
                ->whereKey($conversion->id)
                ->whereNull('converted_at')
                ->update(['converted_at' => now()]);

            $test = AbTest::find($conversion->ab_test_id);
            if ($updated && $test) {
                static::increment($test, $conversion->variant, 'conversions');
                $count++;
            }
        }

        return $count;
    }

    /**
     * Totalen per variant, als databasesom.
     *
     * @return array{a: array{views: int, clicks: int, conversions: int}, b: array{views: int, clicks: int, conversions: int}}
     */
    public static function totals(AbTest $test): array
    {
        $rows = AbTestStat::query()
            ->where('ab_test_id', $test->id)
            ->groupBy('variant')
            ->select('variant', DB::raw('SUM(views) as views'), DB::raw('SUM(clicks) as clicks'), DB::raw('SUM(conversions) as conversions'))
            ->get()
            ->keyBy('variant');

        $totals = [];
        foreach (['a', 'b'] as $variant) {
            $row = $rows->get($variant);
            $totals[$variant] = [
                'views' => (int) ($row->views ?? 0),
                'clicks' => (int) ($row->clicks ?? 0),
                'conversions' => (int) ($row->conversions ?? 0),
            ];
        }

        return $totals;
    }

    /** De successen voor het doel van de test: kliks of conversies. */
    public static function successColumn(AbTest $test): string
    {
        return $test->goal === AbTest::GOAL_CLICK ? 'clicks' : 'conversions';
    }

    /**
     * @return array{a: array<string, mixed>, b: array<string, mixed>, z: float|null, p: float|null, significant: bool, leader: string|null}
     */
    public static function statistics(AbTest $test): array
    {
        $totals = static::totals($test);
        $column = static::successColumn($test);

        $result = Significance::twoProportion(
            $totals['a'][$column],
            $totals['a']['views'],
            $totals['b'][$column],
            $totals['b']['views'],
        );

        foreach (['a', 'b'] as $variant) {
            $views = $totals[$variant]['views'];
            $totals[$variant]['rate'] = $views > 0 ? $totals[$variant][$column] / $views : null;
        }

        return [
            'a' => $totals['a'],
            'b' => $totals['b'],
            ...$result,
        ];
    }
}
