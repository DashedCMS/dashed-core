<?php

namespace Dashed\DashedCore;

use Dashed\DashedArticles\DashedArticlesPlugin;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceChannable\DashedEcommerceChannablePlugin;
use Dashed\DashedEcommerceCore\DashedEcommerceCorePlugin;
use Dashed\DashedEcommerceEboekhouden\DashedEcommerceEboekhoudenPlugin;
use Dashed\DashedEcommerceExactonline\DashedEcommerceExactonlinePlugin;
use Dashed\DashedEcommerceMollie\DashedEcommerceMolliePlugin;
use Dashed\DashedEcommerceMontaportal\DashedEcommerceMontaportalPlugin;
use Dashed\DashedEcommerceMultiSafePay\DashedEcommerceMultisafepayPlugin;
use Dashed\DashedEcommerceMyParcel\DashedEcommerceMyParcelPlugin;
use Dashed\DashedEcommercePaynl\DashedEcommercePaynlPlugin;
use Dashed\DashedEcommerceSendy\DashedEcommerceSendyPlugin;
use Dashed\DashedEcommerceWebwinkelkeur\DashedEcommerceWebwinkelkeurPlugin;
use Dashed\DashedFiles\DashedFilesPlugin;
use Dashed\DashedForms\DashedFormsPlugin;
use Dashed\DashedMenus\DashedMenusPlugin;
use Dashed\DashedPages\DashedPagesPlugin;
use Dashed\DashedTernair\DashedTernairPlugin;
use Dashed\DashedTranslations\DashedTranslationsPlugin;
use Filament\Forms\Get;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\SpatieLaravelTranslatablePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Builder;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Forms\Components\Actions\Action;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
    ];

    protected static $builderBlocksActivated = [
        'active' => false,
    ];

    public function builder(string $name, null|string|array $blocks = null): self|array
    {
        if (!$blocks) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = array_merge(static::$builders[$name] ?? [], $blocks);

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
            if (!View::exists('components.blocks.' . $block->getName())) {
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
                            ->options(GlobalBlock::all()->mapWithKeys(fn($block) => [$block->id => $block->name]))
                            ->placeholder('Kies een globaal blok')
                            ->hintAction(
                                Action::make('editGlobalBlock')
                                    ->label('Bewerk globaal blok')
                                    ->url(fn(Get $get) => route('filament.dashed.resources.global-blocks.edit', ['record' => $get('globalBlock')]))
                                    ->openUrlInNewTab()
                                    ->visible(fn(Get $get) => $get('globalBlock'))
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
            'hasResults' => collect($results)->filter(fn($result) => $result['hasResults'])->count() > 0,
        ];
    }

    public function isCMSRoute(): bool
    {
        if (str(request()->url())->contains('form/post')) {
            return false;
        }

        return str(request()->url())->contains(config('filament.path')) || str(request()->url())->contains('livewire');
    }

    public function getFilamentPanelItems(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('dashed')
            ->path(config('dashed-core.dashed_cms.path', 'dashed'))
            ->login()
            ->colors([
                'primary' => config('dashed-core.dashed_cms.primary_color', '#00D2CD'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
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
            ->brandName(Customsetting::get('site_name', null, env('APP_NAME')));

        return $panel;
    }

    public function getFilamentPluginItems(): array
    {
        $plugins = [
            SpatieLaravelTranslatablePlugin::make()
                ->defaultLocales(array_keys(Locales::getLocalesArray())),
            mediaHelper()->plugin(),
        ];

        foreach(cms()->builder('plugins') as $plugin) {
            $plugins[] = $plugin;
        }

        return $plugins;

        return [
            new DashedCorePlugin(),
            new DashedArticlesPlugin(),
            new DashedEcommerceChannablePlugin(),
            new DashedEcommerceCorePlugin(),
            new DashedEcommerceEboekhoudenPlugin(),
            new DashedEcommerceExactonlinePlugin(),
            new DashedEcommerceSendyPlugin(),
            new DashedEcommerceMolliePlugin(),
            new DashedEcommerceMontaportalPlugin(),
            new DashedEcommerceMultisafepayPlugin(),
            new DashedEcommercePaynlPlugin(),
            new DashedEcommerceWebwinkelkeurPlugin(),
            new DashedEcommerceMyParcelPlugin(),
            new DashedFilesPlugin(),
            new DashedFormsPlugin(),
            new DashedMenusPlugin(),
            new DashedPagesPlugin(),
            new DashedTranslationsPlugin(),
            new DashedTernairPlugin(),
        ];
    }
}
