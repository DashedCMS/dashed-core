<?php

namespace Dashed\DashedCore;

use Filament\Panel;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\View;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Dashed\DashedCore\Classes\Locales;
use Filament\Forms\Components\Builder;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Forms\Components\RichEditor;
use Filament\Http\Middleware\Authenticate;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\AccountHelper;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class CMSManager
{
    protected static $builders = [
        'sites' => [],
        'forms' => [],
        'blocks' => [],
        'builderBlockClasses' => [],
        'createDefaultPages' => [],
        'publishOnUpdate' => [],
        'content' => [],
        'routeModels' => [],
        'settingPages' => [],
        'frontendMiddlewares' => [],
        'plugins' => [],
        'themes' => [
            'dashed' => 'Dashed',
        ],
        'editor' => RichEditor::class,
        'editorAttributes' => [],
        'ignorableKeysForTranslations' => [],
        'ignorableColumnsForTranslations' => [],
        'classes' => [],
    ];

    protected static $builderBlocksActivated = [
        'active' => false,
    ];

    public function builder(string $name, null|string|array $blocks = null): self|array|string
    {
        if (! $blocks) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = array_merge(static::$builders[$name] ?? [], $blocks);

        return $this;
    }

    public function class(string $name, string|array $value = null): self|array|string
    {
        if (! $value) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = $value;

        return $this;
    }

    public function activateBuilderBlockClasses(): self|array
    {
        if (static::$builderBlocksActivated['active']) {
            return $this;
        }

        foreach (collect(cms()->builder('builderBlockClasses'))->sortKeysDesc()->toArray() as $class => $method) {
            if (is_array($method)) {
                foreach ($method as $m) {
                    $class::$m();
                }
            } else {
                $class::$method();
            }
        }

        static::$builderBlocksActivated['active'] = true;

        return $this;
    }

    public function getFilamentBuilderBlock(string $name = 'content', string $blocksName = 'blocks', bool $globalBlockChooser = true): Builder
    {
        self::activateBuilderBlockClasses();

        $blocks = cms()->builder($blocksName);

        foreach ($blocks as $key => $block) {
            foreach ($blocks as $duplicateKey => $duplicateBlock) {
                if ($key !== $duplicateKey && $block->getName() === $duplicateBlock->getName()) {
                    unset($blocks[$key]);
                }
            }
        }

        foreach ($blocks as $key => $block) {
            if (! View::exists('components.blocks.' . $block->getName())) {
                unset($blocks[$key]);
            }
        }

        return Builder::make($name)
            ->blocks(array_merge([
                Builder\Block::make('globalBlock')
                    ->label('Globaal blok')
                    ->visible(GlobalBlock::count() > 0)
                    ->schema([
                        Select::make('globalBlock')
                            ->label('Globaal blok')
                            ->options(GlobalBlock::all()->mapWithKeys(fn ($block) => [$block->id => $block->name]))
                            ->placeholder('Kies een globaal blok')
                            ->hintAction(
                                Action::make('editGlobalBlock')
                                    ->label('Bewerk globaal blok')
                                    ->url(fn (Get $get) => route('filament.dashed.resources.global-blocks.edit', ['record' => $get('globalBlock')]))
                                    ->openUrlInNewTab()
                                    ->visible(fn (Get $get) => $get('globalBlock'))
                            )
                            ->reactive()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->lazy()
                            ->reactive()
                            ->columnSpanFull(),
                    ]),
            ], $blocks))
            ->collapsible(true)
            ->blockIcons()
            ->blockNumbers()
            ->blockPickerColumns(3)
            ->blockLabels()
            ->cloneable()
            ->reorderable()
            ->columnSpanFull();
    }

    public function getSearchResults(?string $query): array
    {
        $results = [];

        if ($query) {
            foreach (static::builder('routeModels') as $model) {
                $queryResults = $model['class']::search($query)->get();
                $results[$model['class']] = array_merge($model, [
                    'results' => $queryResults,
                    'count' => $queryResults->count(),
                    'hasResults' => $queryResults->count() > 0,
                ]);
            }
        }

        return [
            'results' => $results,
            'count' => collect($results)->sum('count'),
            'hasResults' => collect($results)->filter(fn ($result) => $result['hasResults'])->count() > 0,
        ];
    }

    public function isCMSRoute(string $panelId = null): bool
    {
        $name = Route::currentRouteName();

        if (! $name) {
            return false;
        }

        return $panelId
            ? str_starts_with($name, $panelId . '.')
            : collect(Filament::getPanels())
                ->keys()
                ->contains(fn ($id) => str_starts_with($name, $id . '.'));
    }

    public function getFilamentPanelItems(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('dashed')
            ->path(config('dashed-core.dashed_cms.path', 'dashed'))
            ->login()
//            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile()
            ->colors([
                'primary' => config('dashed-core.dashed_cms.primary_color', '#00D2CD'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Dashed\DashedEcommerceCore\Filament\Pages\Dashboard\Dashboard::class,
            ])
            ->databaseNotifications()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
//            ->brandLogo(fn () => mediaHelper()->getSingleMedia(Customsetting::get('site_logo'))->url)
            ->brandName(Customsetting::get('site_name', null, 'DashedCMS'));

        return $panel;
    }

    public function getFilamentPluginItems(): array
    {
        $plugins = [
            SpatieTranslatablePlugin::make()
                ->defaultLocales(array_keys(Locales::getLocalesArray())),
//            mediaHelper()->plugin(),
        ];

        foreach (cms()->builder('plugins') as $plugin) {
            $plugins[] = $plugin;
        }

        return $plugins;
    }

    public function registerRouteModel($class, $name, $nameField = 'name'): void
    {
        $className = str(str($class)->explode("\\")->last())->camel()->singular()->toString();

        cms()->builder('routeModels', [
            $className => [
                'name' => $name,
                'pluralName' => str($name)->plural(),
                'class' => $class,
                'nameField' => $nameField,
            ],
        ]);
    }

    public function registerSettingsPage($settingsPage, $name, $icon = 'rss', $description = ''): void
    {
        $className = str(str($settingsPage)->explode("\\")->last())->camel()->singular()->toString();

        cms()->builder('settingPages', [
            $className => [
                'name' => $name,
                'description' => $description ?: 'Instellingen voor ' . str($name)->plural()->lower(),
                'icon' => $icon,
                'page' => $settingsPage,
            ],
        ]);
    }

    public function checkModelPassword()
    {
        $model = app('view')->getShared()['model'] ?? null;

        if (! $model?->metadata?->password) {
            return null;
        }

        if (! self::hasAccessToModel($model)) {
            $data = Crypt::encrypt([
                'model' => $model::class,
                'modelId' => $model->id,
            ]);

            return redirect(AccountHelper::getPasswordProtectionUrl() . "?data={$data}");
        }

        return null;
    }

    public function hasAccessToModel($model): bool
    {
        $key = sprintf('%s_%d_password', $model::class, $model->id);

        if (session($key) !== $model->metadata->password) {
            return false;
        }

        return true;
    }

    public function setEditor($class)
    {
        $this->builder('editor', $class);
    }

    public function setEditorAttributes($attributes)
    {
        $this->builder('editorAttributes', $attributes);
    }

    public function editorField(string $name = 'content', ?string $label = null)
    {
        $builder = $this->builder('editor')::make($name);

        if ($label) {
            $builder->label($label);
        }

        return $builder;
    }

    public function convertToHtml($content): string
    {
        return RichEditor\RichContentRenderer::make($content)->toHtml();
        //        return tiptap_converter()->asHTML($content);
    }
}
