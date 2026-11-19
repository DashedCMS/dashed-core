<?php

namespace Dashed\DashedCore\Classes\Changes;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Models\ScheduledChange;
use Dashed\DashedCore\Filament\Resources\ScheduledChangeResource;

/**
 * Voert ingeplande wijzigingen uit en draait ze terug. Draait elke minuut
 * via dashed:run-scheduled-changes.
 *
 * Idempotent: elke rij wordt geclaimd met een voorwaardelijke update op de
 * status, binnen dezelfde transactie als het wegschrijven. Twee gelijktijdige
 * runs (of een herhaalde aanroep) kunnen dezelfde rij dus nooit twee keer
 * doorvoeren, en een fout halverwege rolt ook de claim terug.
 */
class ScheduledChangeRunner
{
    public const LOCK = 'dashed:scheduled-changes';

    public const BATCH = 500;

    public static function hasWork(): bool
    {
        return rescue(fn () => ScheduledChange::query()->due()->exists()
            || ScheduledChange::query()->dueForRevert()->exists(), false, false);
    }

    /**
     * @return array{applied: int, reverted: int, failed: int}
     */
    public static function runDue($now = null): array
    {
        $result = ['applied' => 0, 'reverted' => 0, 'failed' => 0];
        $lock = Cache::lock(self::LOCK, 600);

        if (! $lock->get()) {
            return $result;
        }

        try {
            $due = ScheduledChange::query()->due($now)->orderBy('run_at')->orderBy('id')->limit(self::BATCH)->get();

            foreach ($due as $change) {
                static::apply($change) ? $result['applied']++ : ($change->status === ScheduledChange::STATUS_FAILED ? $result['failed']++ : null);
            }

            $reverts = ScheduledChange::query()->dueForRevert($now)->orderBy('revert_at')->orderBy('id')->limit(self::BATCH)->get();

            foreach ($reverts as $change) {
                static::revert($change) ? $result['reverted']++ : ($change->status === ScheduledChange::STATUS_REVERT_FAILED ? $result['failed']++ : null);
            }
        } finally {
            $lock->release();
        }

        return $result;
    }

    public static function apply(ScheduledChange $change): bool
    {
        try {
            $applied = DB::transaction(function () use ($change): bool {
                $claimed = ScheduledChange::query()
                    ->whereKey($change->getKey())
                    ->where('status', ScheduledChange::STATUS_SCHEDULED)
                    ->update(['status' => ScheduledChange::STATUS_APPLIED, 'applied_at' => now()]);

                if (! $claimed) {
                    return false;
                }

                $record = $change->resolveRecord();

                if (! $record) {
                    throw new \RuntimeException(__('Het record bestaat niet meer.'));
                }

                $fields = ChangeFields::for($record);
                $plan = ChangeSet::plan($record, $fields, $change->changes ?? [], $change->locale);

                if ($plan) {
                    ChangeSet::write($record, $fields, array_map(fn ($row) => $row['new'], $plan), $change->locale);
                }

                $change->forceFill([
                    'status' => ScheduledChange::STATUS_APPLIED,
                    'applied_at' => now(),
                    'original' => array_map(fn ($row) => $row['old'], $plan),
                    'applied' => array_map(fn ($row) => $row['new'], $plan),
                    'error' => null,
                ])->save();

                return true;
            });
        } catch (Throwable $e) {
            report($e);
            static::markFailed($change, ScheduledChange::STATUS_FAILED, $e, __('Ingeplande wijziging mislukt'));

            return false;
        }

        if (! $applied) {
            $change->refresh();
        }

        return $applied;
    }

    public static function revert(ScheduledChange $change): bool
    {
        try {
            $reverted = DB::transaction(function () use ($change): bool {
                $claimed = ScheduledChange::query()
                    ->whereKey($change->getKey())
                    ->where('status', ScheduledChange::STATUS_APPLIED)
                    ->whereNotNull('revert_at')
                    ->update(['status' => ScheduledChange::STATUS_REVERTED, 'reverted_at' => now()]);

                if (! $claimed) {
                    return false;
                }

                $record = $change->resolveRecord();

                if (! $record) {
                    throw new \RuntimeException(__('Het record bestaat niet meer.'));
                }

                $fields = ChangeFields::for($record);
                $result = ChangeSet::restorable($record, $fields, $change->applied ?? [], $change->original ?? [], $change->locale);

                if ($result['restore']) {
                    ChangeSet::write($record, $fields, $result['restore'], $change->locale);
                }

                $change->forceFill([
                    'status' => ScheduledChange::STATUS_REVERTED,
                    'reverted_at' => now(),
                    'skipped' => $result['skipped'] ?: null,
                    'error' => null,
                ])->save();

                return true;
            });
        } catch (Throwable $e) {
            report($e);
            static::markFailed($change, ScheduledChange::STATUS_REVERT_FAILED, $e, __('Terugdraaien van ingeplande wijziging mislukt'));

            return false;
        }

        if (! $reverted) {
            $change->refresh();
        }

        return $reverted;
    }

    /**
     * Zet een mislukte wijziging klaar voor een nieuwe poging bij de
     * eerstvolgende ronde.
     */
    public static function retry(ScheduledChange $change): void
    {
        $status = match ($change->status) {
            ScheduledChange::STATUS_FAILED => ScheduledChange::STATUS_SCHEDULED,
            ScheduledChange::STATUS_REVERT_FAILED => ScheduledChange::STATUS_APPLIED,
            default => null,
        };

        if ($status) {
            $change->forceFill(['status' => $status, 'error' => null])->save();
        }
    }

    protected static function markFailed(ScheduledChange $change, string $status, Throwable $e, string $title): void
    {
        $change->forceFill(['status' => $status, 'error' => mb_substr($e->getMessage(), 0, 2000)])->save();

        $record = rescue(fn () => $change->resolveRecord(), null, false);
        $body = __(':model ":naam": :wijziging. Fout: :fout', [
            'model' => ChangeFields::modelLabel($change->model_type),
            'naam' => ChangeFields::recordLabel($record, (string) $change->model_id),
            'wijziging' => rescue(fn () => $change->summary(), '', false),
            'fout' => $e->getMessage(),
        ]);

        ChangeFailureNotifier::notify($title, $body, rescue(fn () => ScheduledChangeResource::getUrl(), null, false));
    }
}
