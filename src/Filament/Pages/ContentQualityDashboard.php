<?php

namespace Dashed\DashedCore\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\ContentQuality\ContentQualityScanner;

class ContentQualityDashboard extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 50;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Content-kwaliteit';

    protected static ?string $title = 'Content-kwaliteit';

    protected static ?string $slug = 'content-quality';

    protected string $view = 'dashed-core::pages.content-quality-dashboard';

    public ?string $selectedCheck = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user();
    }

    public function getCardsProperty(): array
    {
        return app(ContentQualityScanner::class)->counts(Sites::getActive());
    }

    public function getIssuesProperty(): Collection
    {
        if (! $this->selectedCheck) {
            return collect();
        }

        return app(ContentQualityScanner::class)->issues(Sites::getActive(), $this->selectedCheck);
    }

    public function selectCheck(string $key): void
    {
        $this->selectedCheck = $key;
    }

    public function rescan(): void
    {
        app(ContentQualityScanner::class)->rescan(Sites::getActive());
        $this->dispatch('$refresh');
    }
}
