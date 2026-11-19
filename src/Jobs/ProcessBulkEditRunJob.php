<?php

namespace Dashed\DashedCore\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Models\BulkEditRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\BackgroundTask;
use Dashed\DashedCore\Classes\Changes\BulkEditor;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Dashed\DashedCore\Filament\Resources\BulkEditRunResource;

/**
 * Een grote bulkbewerking (of het ongedaan maken ervan) in de wachtrij, met
 * voortgang in het takenicoon.
 */
class ProcessBulkEditRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const MODE_APPLY = 'apply';
    public const MODE_UNDO = 'undo';

    public $timeout = 1800;

    public $tries = 1;

    public function __construct(public int $runId, public string $mode = self::MODE_APPLY)
    {
    }

    public function handle(): void
    {
        $run = BulkEditRun::query()->find($this->runId);

        if (! $run) {
            return;
        }

        $undo = $this->mode === self::MODE_UNDO;
        $label = $undo
            ? __('Bulkbewerking ongedaan maken (:model)', ['model' => ChangeFields::modelLabel($run->model_type)])
            : __('Bulkbewerking (:model)', ['model' => ChangeFields::modelLabel($run->model_type)]);

        $task = BackgroundTask::begin('bulk-edit', $label, $undo ? $run->changed : $run->total, $run->user_id);

        try {
            $run = $undo ? BulkEditor::undo($run, $task) : BulkEditor::process($run, $task);
        } catch (Throwable $e) {
            $task->fail($e);

            throw $e;
        }

        $message = $undo
            ? __(':aantal records teruggezet, :overgeslagen met velden die sindsdien opnieuw gewijzigd waren.', ['aantal' => $run->changed, 'overgeslagen' => $run->undo_skipped])
            : __(':gewijzigd van :totaal records gewijzigd.', ['gewijzigd' => $run->changed, 'totaal' => $run->total]);

        $task->finish($message, rescue(fn () => BulkEditRunResource::getUrl(), null, false), __('Bulkbewerkingen'));
    }

    public function failed(?Throwable $e): void
    {
        $run = BulkEditRun::query()->find($this->runId);

        if (! $run) {
            return;
        }

        // Een run die tijdens het uitvoeren afbrak heeft al items voor de
        // records die wel lukten; die blijven ongedaan te maken.
        $run->forceFill([
            'status' => $this->mode === self::MODE_UNDO || $run->changed > 0 ? BulkEditRun::STATUS_COMPLETED : BulkEditRun::STATUS_FAILED,
            'error' => $e ? mb_substr($e->getMessage(), 0, 2000) : $run->error,
        ])->save();
    }
}
