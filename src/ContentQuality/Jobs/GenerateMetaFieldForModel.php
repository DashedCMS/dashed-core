<?php

namespace Dashed\DashedCore\ContentQuality\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\BackgroundTask;
use Dashed\DashedCore\ContentQuality\MetaFieldGenerator;

class GenerateMetaFieldForModel implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const TASK_TYPE = 'ai-meta-fields';

    public int $timeout = 600;

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
        public string $field,
        public array $missingLocales,
        public bool $rewrite = false,
        public ?int $taskId = null,
    ) {
    }

    public function handle(): void
    {
        $model = $this->modelClass::find($this->modelId);
        if (! $model) {
            $this->advanceTask();

            return;
        }

        $generator = app(MetaFieldGenerator::class);
        $generated = $this->rewrite
            ? $generator->rewrite($model, $this->field, $this->missingLocales)
            : $generator->generate($model, $this->field, $this->missingLocales);
        if ($generated === []) {
            $this->advanceTask();

            return;
        }

        $metadata = $model->metadata ?: $model->metadata()->make();
        foreach ($generated as $locale => $value) {
            $metadata->setTranslation($this->field, $locale, $value);
        }
        $model->metadata()->save($metadata);

        $this->advanceTask();
    }

    /**
     * Ook een item dat stukliep telt als afgehandeld, anders blijft de taak
     * van een bulkronde eeuwig op bezig staan.
     */
    public function failed(Throwable $e): void
    {
        $this->advanceTask();
    }

    /**
     * Alleen bij een bulkronde vanuit het kwaliteitsdashboard (taskId
     * gevuld). Telt in de database op en niet op het model, want de jobs
     * lopen naast elkaar op meerdere workers.
     */
    protected function advanceTask(): void
    {
        if ($this->taskId === null) {
            return;
        }

        rescue(function () {
            BackgroundTask::query()->whereKey($this->taskId)->active()->increment('done');

            $task = BackgroundTask::query()->whereKey($this->taskId)->active()->first();

            // Twee jobs kunnen tegelijk de laatste zijn: alleen wie de status
            // zelf omzet rondt af, anders krijgt de beheerder twee meldingen.
            if ($task && $task->total && $task->done >= $task->total
                && BackgroundTask::query()->whereKey($this->taskId)->active()->update(['status' => BackgroundTask::STATUS_COMPLETED])) {
                $task->finish(
                    __(':aantal items verwerkt.', ['aantal' => $task->total]),
                    rescue(fn () => \Dashed\DashedCore\Filament\Pages\ContentQualityDashboard::getUrl(), null, false),
                    __('Naar Content-kwaliteit'),
                );
            }
        }, report: false);
    }
}
