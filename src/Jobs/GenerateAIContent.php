<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Dashed\DashedCore\Classes\OpenAIHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateAIContent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 5;
    public $timeout = 1200;

    public $model;
    public string $column;
    public string $content;
    public string $locale;

    /**
     * Create a new job instance.
     */
    public function __construct($model, string $column, string $content, string $locale)
    {
        $this->model = $model;
        $this->column = $column;
        $this->content = $content;
        $this->locale = $locale;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $description = OpenAIHelper::runPrompt(prompt: $this->content);
        $this->model->setTranslation($this->column, $this->locale, $description);
        $this->model->saveQuietly();
    }
}
