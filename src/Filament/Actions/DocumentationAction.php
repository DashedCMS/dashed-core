<?php

namespace Dashed\DashedCore\Filament\Actions;

use Illuminate\Support\Str;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Dashed\DashedCore\Services\DocsRegistry;

class DocumentationAction extends Action
{
    protected ?string $explicitTopicKey = null;

    public static function getDefaultName(): ?string
    {
        return 'documentation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Uitleg')
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->modalHeading(fn () => $this->resolveDoc()['title'] ?? 'Uitleg')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten')
            ->modalWidth('2xl')
            ->modalContent(fn (): View => view('dashed-core::docs.modal', [
                'doc' => $this->resolveDoc(),
            ]));
    }

    public function topic(string $key): static
    {
        $this->explicitTopicKey = $key;

        return $this;
    }

    protected function resolveDoc(): ?array
    {
        $registry = app(DocsRegistry::class);

        if ($this->explicitTopicKey !== null) {
            return $this->prepareDoc($registry->getTopic($this->explicitTopicKey));
        }

        $component = $this->getLivewire();

        if ($component === null) {
            return null;
        }

        if ($component instanceof \Filament\Resources\Pages\Page) {
            $resource = $this->readStaticResource($component);

            if ($resource !== null) {
                return $this->prepareDoc($registry->getForResource($resource));
            }
        }

        return $this->prepareDoc($registry->getForSettingsPage(get_class($component)));
    }

    protected function readStaticResource(object $component): ?string
    {
        try {
            $reflection = new \ReflectionClass($component);
            if (! $reflection->hasProperty('resource')) {
                return null;
            }

            $property = $reflection->getProperty('resource');
            $property->setAccessible(true);
            $value = $property->getValue();

            return is_string($value) && $value !== '' ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function prepareDoc(?array $doc): ?array
    {
        if ($doc === null) {
            return null;
        }

        $sections = [];
        foreach ($doc['sections'] ?? [] as $section) {
            $heading = $section['heading'] ?? '';
            $body = $section['body'] ?? '';

            $sections[] = [
                'heading' => $heading,
                'body' => $body !== '' ? new HtmlString(Str::markdown($body)) : new HtmlString(''),
            ];
        }

        return [
            'title' => $doc['title'] ?? null,
            'intro' => $doc['intro'] ?? null,
            'sections' => $sections,
            'tips' => $doc['tips'] ?? [],
            'fields' => $doc['fields'] ?? [],
        ];
    }
}
