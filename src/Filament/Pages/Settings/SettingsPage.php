<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class SettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Instellingen';
    protected static ?string $title = 'Instellingen';

    protected static string | UnitEnum | null $navigationGroup = 'Overige';

    protected static ?int $navigationSort = 100000;

    protected string $view = 'dashed-core::settings.pages.settings';

    public string $search = '';

    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        foreach (cms()->builder('settingPages') as $page) {
            $permission = $page['permission'] ?? null;
            if (! $permission || auth()->user()->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Livewire computed property: $this->settingPages
     */
    public function getSettingPagesProperty(): Collection
    {
        $user = auth()->user();

        $pages = collect(cms()->builder('settingPages'))
            ->filter(function ($page) use ($user) {
                $permission = $page['permission'] ?? null;

                return ! $permission || $user->can($permission);
            });

        $search = trim($this->search);

        if ($search === '') {
            return $pages;
        }

        return $pages->filter(function ($page) use ($search) {
            $name = (string) ($page['name'] ?? '');
            $description = (string) ($page['description'] ?? '');

            return Str::contains(Str::lower($name . ' ' . $description), Str::lower($search));
        })->values();
    }
}
