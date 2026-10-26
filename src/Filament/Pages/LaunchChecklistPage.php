<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Launch\LaunchChecklist;

/**
 * De livegang-checklist (zie LaunchChecklist). Tot de livegang staat er een
 * badge "9/14" in het menu en een balk op het dashboard; daarna alleen nog
 * het menu-item, laag in de groep Systeem.
 */
class LaunchChecklistPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $slug = 'livegang';

    protected static ?string $title = 'Livegang-checklist';

    protected static ?string $navigationLabel = 'Livegang-checklist';

    protected static string | \UnitEnum | null $navigationGroup = 'Systeem';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?int $navigationSort = 95;

    protected string $view = 'dashed-core::pages.launch-checklist';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'superadmin'], true);
    }

    public static function getNavigationBadge(): ?string
    {
        if (LaunchChecklist::launchedAt()) {
            return null;
        }

        $progress = rescue(fn () => LaunchChecklist::progress(), null, report: false);

        return $progress ? $progress['done'].'/'.$progress['total'] : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function toggle(string $key): void
    {
        $item = collect(LaunchChecklist::items())->firstWhere('key', $key);

        if ($item && $item['manual']) {
            LaunchChecklist::toggle($key);
        }
    }

    protected function getHeaderActions(): array
    {
        $launched = LaunchChecklist::launchedAt();

        return [
            Action::make('launch')
                ->label($launched ? __('Livegang terugdraaien') : __('Livegang afgerond'))
                ->icon($launched ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-rocket-launch')
                ->color($launched ? 'gray' : 'success')
                ->requiresConfirmation()
                ->modalDescription($launched ? null : __('De balk op het dashboard en de badge in het menu verdwijnen. De checklist blijft hier bereikbaar.'))
                ->action(function () use ($launched) {
                    LaunchChecklist::markLaunched(! $launched);
                    Notification::make()->title($launched ? __('Livegang teruggedraaid') : __('Gefeliciteerd met de livegang'))->success()->send();
                }),
        ];
    }
}
