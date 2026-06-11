<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCore\Services\VisualEditor\VisualEditor;
use Dashed\DashedCore\Services\VisualEditor\BlockSchemaResolver;

class VisualEditorBlockPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'visual-editor/block';

    protected string $view = 'dashed-core::visual-editor.block-page';

    public string $modelType = '';

    public int|string $modelId = 0;

    public string $locale = '';

    public int $block = 0;

    public string $blockType = '';

    public array $data = [];

    public function mount(
        ?string $model_type = null,
        int|string|null $model_id = null,
        ?string $locale = null,
        int|string|null $block = null,
    ): void {
        $model_type = $model_type ?? (string) request()->query('model_type', '');
        $model_id = $model_id ?? request()->query('model_id', 0);
        $locale = $locale ?? (string) request()->query('locale', '');
        $block = (int) ($block ?? request()->query('block', 0));

        if (! app(VisualEditor::class)->isAdmin()) {
            abort(403);
        }

        $allowed = collect(cms()->builder('routeModels'))->pluck('class')->filter()->contains($model_type);
        if (! $allowed || ! class_exists($model_type) || ! method_exists($model_type, 'customBlocks')) {
            abort(422, 'Niet-bewerkbaar modeltype.');
        }

        $model = $model_type::query()->find($model_id);
        if (! $model || ! $model->customBlocks) {
            abort(404);
        }

        $blocks = $model->customBlocks->getTranslation('blocks', $locale);
        if (! is_array($blocks) || ! isset($blocks[$block]) || ! is_array($blocks[$block]['data'] ?? null)) {
            abort(404, 'Blok niet gevonden.');
        }

        $this->modelType = $model_type;
        $this->modelId = $model_id;
        $this->locale = $locale;
        $this->block = $block;
        $this->blockType = (string) ($blocks[$block]['type'] ?? '');

        $this->form->fill($blocks[$block]['data']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema(app(BlockSchemaResolver::class)->components($this->blockType))
            ->statePath('data');
    }

    public function save(): void
    {
        if (! app(VisualEditor::class)->isAdmin()) {
            abort(403);
        }

        $model = $this->modelType::query()->find($this->modelId);
        if (! $model || ! $model->customBlocks) {
            abort(404);
        }

        $blocks = $model->customBlocks->getTranslation('blocks', $this->locale);
        if (! is_array($blocks) || ! isset($blocks[$this->block])) {
            abort(404);
        }

        $blocks[$this->block]['data'] = array_replace(
            is_array($blocks[$this->block]['data'] ?? null) ? $blocks[$this->block]['data'] : [],
            $this->form->getState()
        );
        $model->customBlocks->setTranslation('blocks', $this->locale, $blocks)->save();

        if (method_exists($model, 'clearContentBlockCache')) {
            try {
                $model->clearContentBlockCache(collect($blocks)->pluck('type')->filter()->unique()->values()->all());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->dispatch('dashed-block-saved');
        Notification::make()->title('Opgeslagen')->success()->send();
    }
}
