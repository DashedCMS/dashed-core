<?php

namespace Dashed\DashedCore\Classes\Changes;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\BulkEditRun;
use Dashed\DashedCore\Models\BulkEditItem;
use Dashed\DashedCore\Models\BackgroundTask;
use Illuminate\Contracts\Auth\Authenticatable;
use Dashed\DashedCore\Jobs\ProcessBulkEditRunJob;
use Dashed\DashedCore\Filament\Resources\BulkEditRunResource;

/**
 * Bulkbewerking met voorbeeld en ongedaan maken. Elke wijziging gaat per
 * record via de gewone Eloquent-save (ChangeSet::write), zodat events,
 * feeds en caches meelopen. Per record worden de oude en nieuwe waarden
 * bewaard; ongedaan maken zet alleen velden terug die nog op de nieuwe
 * waarde staan.
 */
class BulkEditor
{
    /**
     * Tot zoveel records draait een run meteen in het verzoek; daarboven
     * gaat hij via de wachtrij, met voortgang in het takenicoon.
     */
    public const INLINE_LIMIT = 50;

    public const CHUNK = 100;

    /**
     * Het voorbeeld rekent hooguit zoveel records door; bij een grotere
     * selectie zegt het scherm dat het een steekproef is.
     */
    public const PREVIEW_SAMPLE = 200;

    /**
     * @param  iterable<Model>  $records
     * @param  array<string, array{op: string, value: mixed}>  $changes
     * @return array{rows: array<int, array{label: string, fields: array<string, array{old: string, new: string}>}>, changed: int, unchanged: int, total: int}
     */
    public static function preview(string $modelClass, iterable $records, array $changes, ?string $locale = null, int $limit = 50): array
    {
        $fields = ChangeFields::for($modelClass, 'bulk');
        $rows = [];
        $changed = 0;
        $total = 0;

        foreach ($records as $record) {
            $total++;
            $plan = ChangeSet::plan($record, $fields, $changes, $locale);

            if (! $plan) {
                continue;
            }

            $changed++;

            if (count($rows) < $limit) {
                $rows[] = [
                    'label' => ChangeFields::recordLabel($record),
                    'fields' => collect($plan)->mapWithKeys(fn ($row, $key) => [$fields[$key]->getLabel() => [
                        'old' => $fields[$key]->display($row['old']),
                        'new' => $fields[$key]->display($row['new']),
                    ]])->all(),
                ];
            }
        }

        return ['rows' => $rows, 'changed' => $changed, 'unchanged' => $total - $changed, 'total' => $total];
    }

    public static function previewHtml(string $modelClass, iterable $records, array $changes, ?string $locale = null, ?int $selected = null): HtmlString
    {
        if (! $changes) {
            return new HtmlString('<p>' . e(__('Kies eerst minstens één wijziging.')) . '</p>');
        }

        $preview = static::preview($modelClass, $records, $changes, $locale);
        $html = '<style>.dbe-preview{width:100%;border-collapse:collapse;font-size:.875rem}.dbe-preview th,.dbe-preview td{text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgba(127,127,127,.25);vertical-align:top}.dbe-old{text-decoration:line-through;opacity:.6}.dbe-new{font-weight:600}</style>';
        $html .= '<p>' . e(__(':gewijzigd van de :totaal records veranderen, :ongewijzigd blijven gelijk.', [
            'gewijzigd' => $preview['changed'],
            'totaal' => $preview['total'],
            'ongewijzigd' => $preview['unchanged'],
        ])) . '</p>';

        if ($selected !== null && $selected > $preview['total']) {
            $html .= '<p>' . e(__('Dit voorbeeld rekent de eerste :steekproef van de :aantal geselecteerde records door.', ['steekproef' => $preview['total'], 'aantal' => $selected])) . '</p>';
        }

        if ($preview['rows']) {
            $html .= '<table class="dbe-preview"><thead><tr><th>' . e(__('Record')) . '</th><th>' . e(__('Veld')) . '</th><th>' . e(__('Oud')) . '</th><th>' . e(__('Nieuw')) . '</th></tr></thead><tbody>';

            foreach ($preview['rows'] as $row) {
                $first = true;

                foreach ($row['fields'] as $label => $values) {
                    $html .= '<tr><td>' . ($first ? e($row['label']) : '') . '</td><td>' . e($label) . '</td><td class="dbe-old">' . e($values['old']) . '</td><td class="dbe-new">' . e($values['new']) . '</td></tr>';
                    $first = false;
                }
            }

            $html .= '</tbody></table>';

            if ($preview['changed'] > count($preview['rows'])) {
                $html .= '<p>' . e(__('En nog :aantal records.', ['aantal' => $preview['changed'] - count($preview['rows'])])) . '</p>';
            }
        }

        return new HtmlString($html);
    }

    /**
     * Legt de run vast en voert hem uit: direct bij een kleine selectie,
     * anders via de wachtrij.
     *
     * @param  array<int, int|string>  $ids
     * @param  array<string, array{op: string, value: mixed}>  $changes
     */
    public static function start(string $modelClass, array $ids, array $changes, ?string $locale = null, Authenticatable|int|null $user = null, ?string $label = null): BulkEditRun
    {
        $run = BulkEditRun::query()->create([
            'model_type' => (new $modelClass())->getMorphClass(),
            'label' => $label,
            'locale' => $locale,
            'changes' => $changes,
            'record_ids' => array_values($ids),
            'status' => BulkEditRun::STATUS_QUEUED,
            'total' => count($ids),
            'user_id' => $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user,
        ]);

        if (count($ids) <= self::INLINE_LIMIT) {
            static::process($run);
        } else {
            ProcessBulkEditRunJob::dispatch($run->id, ProcessBulkEditRunJob::MODE_APPLY);
        }

        return $run->refresh();
    }

    public static function process(BulkEditRun $run, ?BackgroundTask $task = null): BulkEditRun
    {
        $claimed = BulkEditRun::query()->whereKey($run->getKey())
            ->where('status', BulkEditRun::STATUS_QUEUED)
            ->update(['status' => BulkEditRun::STATUS_RUNNING]);

        if (! $claimed) {
            return $run->refresh();
        }

        $class = ChangeFieldsClass::resolve($run->model_type);
        $fields = ChangeFields::for($class, 'bulk');
        $done = 0;
        $changed = 0;
        $failed = 0;
        $firstError = null;

        foreach (array_chunk($run->record_ids ?? [], self::CHUNK) as $chunk) {
            $records = $class::query()->whereKey($chunk)->get();

            foreach ($records as $record) {
                try {
                    DB::transaction(function () use ($record, $fields, $run, &$changed) {
                        $plan = ChangeSet::plan($record, $fields, $run->changes ?? [], $run->locale);

                        if (! $plan) {
                            return;
                        }

                        ChangeSet::write($record, $fields, array_map(fn ($row) => $row['new'], $plan), $run->locale);

                        BulkEditItem::query()->create([
                            'bulk_edit_run_id' => $run->id,
                            'model_id' => (string) $record->getKey(),
                            'old' => array_map(fn ($row) => $row['old'], $plan),
                            'new' => array_map(fn ($row) => $row['new'], $plan),
                            'created_at' => now(),
                        ]);

                        $changed++;
                    });
                } catch (Throwable $e) {
                    report($e);
                    $failed++;
                    $firstError ??= ChangeFields::recordLabel($record) . ': ' . $e->getMessage();
                }

                $done++;
            }

            $run->forceFill(['done' => $done, 'changed' => $changed, 'failed' => $failed])->save();
            $task?->progress($done);
        }

        $run->forceFill([
            'status' => BulkEditRun::STATUS_COMPLETED,
            'done' => $done,
            'changed' => $changed,
            'failed' => $failed,
            'error' => $firstError ? mb_substr($firstError, 0, 2000) : null,
        ])->save();

        if ($failed) {
            ChangeFailureNotifier::notify(
                __('Bulkbewerking deels mislukt'),
                __(':aantal records konden niet bijgewerkt worden. Eerste fout: :fout', ['aantal' => $failed, 'fout' => $firstError]),
                rescue(fn () => BulkEditRunResource::getUrl(), null, false),
            );
        }

        return $run;
    }

    /**
     * Start het ongedaan maken: direct bij een kleine run, anders via de
     * wachtrij.
     */
    public static function startUndo(BulkEditRun $run): BulkEditRun
    {
        if ($run->changed <= self::INLINE_LIMIT) {
            return static::undo($run);
        }

        ProcessBulkEditRunJob::dispatch($run->id, ProcessBulkEditRunJob::MODE_UNDO);

        return $run;
    }

    public static function undo(BulkEditRun $run, ?BackgroundTask $task = null): BulkEditRun
    {
        $claimed = BulkEditRun::query()->whereKey($run->getKey())
            ->where('status', BulkEditRun::STATUS_COMPLETED)
            ->update(['status' => BulkEditRun::STATUS_UNDOING]);

        if (! $claimed) {
            return $run->refresh();
        }

        $class = ChangeFieldsClass::resolve($run->model_type);
        $fields = ChangeFields::for($class, 'bulk');
        $skippedRecords = 0;
        $done = 0;
        $firstError = null;

        $run->items()->whereNull('undone_at')->orderBy('id')->chunkById(self::CHUNK, function ($items) use ($class, $fields, $run, $task, &$skippedRecords, &$done, &$firstError) {
            $records = $class::query()->whereKey($items->pluck('model_id')->all())->get()->keyBy(fn (Model $model) => (string) $model->getKey());

            foreach ($items as $item) {
                $record = $records->get($item->model_id);

                try {
                    DB::transaction(function () use ($record, $item, $fields, $run, &$skippedRecords) {
                        if (! $record) {
                            $item->forceFill(['undone_at' => now(), 'skipped' => array_keys($item->new ?? [])])->save();
                            $skippedRecords++;

                            return;
                        }

                        $result = ChangeSet::restorable($record, $fields, $item->new ?? [], $item->old ?? [], $run->locale);

                        if ($result['restore']) {
                            ChangeSet::write($record, $fields, $result['restore'], $run->locale);
                        }

                        if ($result['skipped']) {
                            $skippedRecords++;
                        }

                        $item->forceFill(['undone_at' => now(), 'skipped' => $result['skipped'] ?: null])->save();
                    });
                } catch (Throwable $e) {
                    report($e);
                    $firstError ??= $item->model_id . ': ' . $e->getMessage();
                }

                $done++;
            }

            $task?->progress($done);
        });

        $run->forceFill([
            'status' => $firstError ? BulkEditRun::STATUS_COMPLETED : BulkEditRun::STATUS_UNDONE,
            'undone_at' => $firstError ? null : now(),
            'undo_skipped' => $skippedRecords,
            'error' => $firstError ? mb_substr($firstError, 0, 2000) : $run->error,
        ])->save();

        if ($firstError) {
            ChangeFailureNotifier::notify(
                __('Ongedaan maken van bulkbewerking mislukt'),
                __('Niet alles kon teruggezet worden. Eerste fout: :fout. Probeer het opnieuw vanuit het overzicht.', ['fout' => $firstError]),
                rescue(fn () => BulkEditRunResource::getUrl(), null, false),
            );
        }

        return $run;
    }
}
