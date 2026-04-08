<?php

namespace Dashed\DashedCore\Filament\Pages\Documentation;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\DocumentationArticle;
use Dashed\DashedCore\Classes\DocumentationService;

class DocumentationPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Documentatie';
    protected static ?string $title = 'Documentatie';
    protected static string|UnitEnum|null $navigationGroup = 'Overige';
    protected static ?int $navigationSort = 99999;

    protected string $view = 'dashed-core::documentation.pages.documentation';

    public string $search = '';
    public ?string $activePackage = null;
    public ?string $activePath = null;

    public function mount(): void
    {
        $service = app(DocumentationService::class);
        $first = $service->getFirstArticle();

        if ($first) {
            $this->activePackage = $first->package;
            $this->activePath = $first->path;
        }
    }

    public function getNavigationTreeProperty(): array
    {
        return app(DocumentationService::class)->getNavigationTree();
    }

    public function getActiveArticleProperty(): ?DocumentationArticle
    {
        if (! $this->activePackage || ! $this->activePath) {
            return null;
        }

        return app(DocumentationService::class)->getArticle($this->activePackage, $this->activePath);
    }

    public function getSearchResultsProperty(): Collection
    {
        if (strlen(trim($this->search)) < 2) {
            return collect();
        }

        return app(DocumentationService::class)->search($this->search);
    }

    public function getAdjacentArticlesProperty(): array
    {
        if (! $this->activePackage || ! $this->activePath) {
            return ['previous' => null, 'next' => null];
        }

        return app(DocumentationService::class)->getAdjacentArticles($this->activePackage, $this->activePath);
    }

    public function selectArticle(string $package, string $path): void
    {
        $this->activePackage = $package;
        $this->activePath = $path;
        $this->search = '';
    }
}
