<?php

namespace Dashed\DashedCore\Filament\Pages\Settings;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class SettingsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Instellingen';
    protected static ?string $title = 'Instellingen';

    protected static string | UnitEnum | null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 1;

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
            })
            ->filter(fn ($page) => static::heeftRoute($page));

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

    /**
     * Een instellingenkaart aanmelden (cms()->registerSettingsPage) en de
     * pagina op het paneel aanmelden (->pages([...]) in de plugin) zijn twee
     * losse handelingen in twee bestanden. Ontbreekt de tweede, dan bestaat de
     * route niet en gooit getUrl() in de blade. Dat mag die ene kaart kosten,
     * niet het hele instellingenscherm: zonder dit filter is elk instellingen-
     * scherm onbereikbaar door één verkeerde aanmelding, en dan kun je de
     * instelling die het probleem veroorzaakt ook niet meer bijstellen.
     *
     * Stil is dit niet. De waarschuwing in het log is de enige plek waar een
     * ontbrekende aanmelding in productie nog opvalt; tijdens ontwikkeling
     * valt SettingsPagesRegisteredTest er als eerste over.
     */
    protected static function heeftRoute(array $page): bool
    {
        $class = $page['page'] ?? null;

        if (! $class || ! is_string($class) || ! class_exists($class)) {
            Log::warning('Instellingenkaart overgeslagen: klasse bestaat niet', ['page' => $class]);

            return false;
        }

        try {
            $class::getUrl();
        } catch (RouteNotFoundException $e) {
            Log::warning('Instellingenkaart overgeslagen: de pagina is niet aangemeld op het paneel', ['page' => $class]);

            return false;
        }

        return true;
    }
}
