<?php

namespace Dashed\DashedCore\ContentQuality\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\ContentQuality\MetaFieldGenerator;

class GenerateMetaFieldForModel implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
        public string $field,
        public array $missingLocales,
        public bool $rewrite = false,
    ) {
    }

    public function handle(): void
    {
        $model = $this->modelClass::find($this->modelId);
        if (! $model) {
            return;
        }

        $generator = app(MetaFieldGenerator::class);
        $generated = $this->rewrite
            ? $generator->rewrite($model, $this->field, $this->missingLocales)
            : $generator->generate($model, $this->field, $this->missingLocales);
        if ($generated === []) {
            return;
        }

        $metadata = $model->metadata ?: $model->metadata()->make();
        foreach ($generated as $locale => $value) {
            $metadata->setTranslation($this->field, $locale, $value);
        }
        $model->metadata()->save($metadata);
    }
}
