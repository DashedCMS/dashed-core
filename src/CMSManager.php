<?php

namespace Dashed\DashedCore;

use Filament\Panel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
use Awcodes\RicherEditor\Plugins\IdPlugin;
use Filament\Http\Middleware\Authenticate;
use Dashed\DashedCore\Models\Customsetting;
use Awcodes\RicherEditor\Plugins\LinkPlugin;
use Dashed\DashedCore\Classes\AccountHelper;
use Awcodes\RicherEditor\Plugins\EmbedPlugin;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Awcodes\RicherEditor\Plugins\FullScreenPlugin;
use Awcodes\RicherEditor\Plugins\SourceCodePlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
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
        'ignorableColumnsForTranslationsPerModel' => [],
        'classes' => [],
        'richEditorPlugins' => [],
        'rolePermissions' => [],
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

    /**
     * Register columns that should NOT be auto-translated for a specific
     * model class. Stacks with the global `ignorableColumnsForTranslations`
     * builder - use this when only one model should keep certain columns
     * untranslated (e.g. Norsup dealer-module wil alleen het `name`-veld
     * van de Dealer-modul niet vertalen, andere modellen wel).
     *
     * Usage:
     *
     *     cms()->ignoreTranslatableColumns(
     *         \App\Models\Dealer::class,
     *         ['name'],
     *     );
     *
     * Or via the builder API:
     *
     *     cms()->builder('ignorableColumnsForTranslationsPerModel', [
     *         \App\Models\Dealer::class => ['name'],
     *     ]);
     */
    public function ignoreTranslatableColumns(string $modelClass, array $columns): self
    {
        $existing = static::$builders['ignorableColumnsForTranslationsPerModel'] ?? [];
        $existing[$modelClass] = array_values(array_unique(array_merge(
            $existing[$modelClass] ?? [],
            $columns,
        )));
        static::$builders['ignorableColumnsForTranslationsPerModel'] = $existing;

        return $this;
    }

    /**
     * Resolve the full list of columns to ignore for a given model - the
     * global list PLUS the per-model overrides registered via
     * `ignoreTranslatableColumns()`.
     *
     * @return array<int,string>
     */
    public function ignorableColumnsForTranslations(?object $modelInstance = null): array
    {
        $global = static::$builders['ignorableColumnsForTranslations'] ?? [];

        if ($modelInstance === null) {
            return $global;
        }

        $perModel = static::$builders['ignorableColumnsForTranslationsPerModel'] ?? [];
        $extra = [];

        foreach ($perModel as $fqcn => $columns) {
            if ($modelInstance instanceof $fqcn) {
                $extra = array_merge($extra, $columns);
            }
        }

        return array_values(array_unique(array_merge($global, $extra)));
    }

    protected static array $emailBlocks = [];

    public function emailBlock(string $key, string $blockClass): self
    {
        static::$emailBlocks[$key] = $blockClass;

        return $this;
    }

    /** @return array<string, class-string> */
    public function emailBlocks(): array
    {
        return static::$emailBlocks;
    }

    public function emailTemplateRegistry(): \Dashed\DashedCore\Mail\EmailTemplateRegistry
    {
        return app(\Dashed\DashedCore\Mail\EmailTemplateRegistry::class);
    }

    public function registerMailable(string $mailableClass): self
    {
        $this->emailTemplateRegistry()->register($mailableClass);

        return $this;
    }

    public function docsRegistry(): \Dashed\DashedCore\Services\DocsRegistry
    {
        return app(\Dashed\DashedCore\Services\DocsRegistry::class);
    }

    public function registerResourceDocs(
        string $resource,
        string $title,
        ?string $intro = null,
        array $sections = [],
        array $tips = [],
    ): self {
        $this->docsRegistry()->registerResource($resource, [
            'title' => $title,
            'intro' => $intro,
            'sections' => $sections,
            'tips' => $tips,
        ]);

        return $this;
    }

    public function registerSettingsDocs(
        string $page,
        string $title,
        ?string $intro = null,
        array $sections = [],
        array $tips = [],
        array $fields = [],
    ): self {
        $this->docsRegistry()->registerSettingsPage($page, [
            'title' => $title,
            'intro' => $intro,
            'sections' => $sections,
            'tips' => $tips,
            'fields' => $fields,
        ]);

        return $this;
    }

    public function registerDocTopic(
        string $key,
        string $title,
        ?string $intro = null,
        array $sections = [],
        array $tips = [],
    ): self {
        $this->docsRegistry()->registerTopic($key, [
            'title' => $title,
            'intro' => $intro,
            'sections' => $sections,
            'tips' => $tips,
        ]);

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
        $pages = [];
        $mfaMethods = [];

        $pages[] = \Dashed\DashedCore\Filament\Pages\Dashboard\Dashboard::class;


        if (Customsetting::get('mfa_app_enabled', false)) {
            $mfaMethods[] = AppAuthentication::make()
                ->recoverable()
                ->recoveryCodeCount(10)
                ->brandName(Customsetting::get('site_name', null, 'DashedCMS'));
        }

        if (Customsetting::get('mfa_email_enabled', false)) {
            $mfaMethods[] = EmailAuthentication::make();
        }

        $forceMFA = Customsetting::get('force_mfa', false) ?: false;
        if ($forceMFA && ! count($mfaMethods)) {
            $mfaMethods[] = EmailAuthentication::make();
        }

        if (app()->isLocal()) {
            $forceMFA = false;
        }

        $navigationGroups = collect(static::$builders['navigationGroups'] ?? [])
            ->sortBy(fn ($entry) => $entry['sort'] ?? 100)
            ->keys()
            ->all();

        $panel
            ->default()
            ->id('dashed')
            ->path(config('dashed-core.dashed_cms.path', 'dashed'))
            ->login()
//            ->registration()
            ->unsavedChangesAlerts()
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile()
            ->when(! empty($navigationGroups), fn (Panel $p) => $p->navigationGroups($navigationGroups))
            ->colors([
                'primary' => config('dashed-core.dashed_cms.primary_color', '#00D2CD'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages($pages)
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
            ->multiFactorAuthentication($mfaMethods, isRequired: $forceMFA)
//            ->brandLogo(fn () => mediaHelper()->getSingleMedia(Customsetting::get('site_logo'))->url)
            ->brandName(Customsetting::get('site_name', null, 'DashedCMS'));

        return $panel;
    }

    public function getFilamentPluginItems(): array
    {
        $plugins = [
            SpatieTranslatablePlugin::make()
                ->defaultLocales(array_keys(Locales::getLocalesArray())),
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

    public function registerRolePermissions(string $group, array $permissions): void
    {
        $existing = static::$builders['rolePermissions'][$group] ?? [];
        static::$builders['rolePermissions'][$group] = array_merge($existing, $permissions);
    }

    /**
     * Register a Filament navigation group so it appears in the panel sidebar
     * with a stable sort position. Called by each package that owns a group;
     * if a package isn't installed, its group simply isn't registered and
     * doesn't show up.
     *
     * Sort convention (lower = higher in the sidebar):
     *   10  Content        70  Gebruikers
     *   20  Artikelen      80  Performance
     *   30  E-commerce     90  Routes
     *   40  Producten     100  Overige
     *   50  Formulieren   110  Statistics
     *   60  Marketing     120  Export
     */
    public function registerNavigationGroup(string $name, int $sort = 100): void
    {
        $existing = static::$builders['navigationGroups'][$name] ?? null;

        // First registration wins on sort to avoid plugins fighting over position;
        // explicit re-registration via builder('navigationGroups', ...) still works.
        if ($existing !== null && isset($existing['sort'])) {
            return;
        }

        static::$builders['navigationGroups'][$name] = ['sort' => $sort];
    }

    public function getRolePermissions(): array
    {
        return static::$builders['rolePermissions'];
    }

    public function registerSettingsPage($settingsPage, $name, $icon = 'rss', $description = '', ?string $permission = null): void
    {
        $className = str(str($settingsPage)->explode("\\")->last())->camel()->singular()->toString();

        // Auto-generate permission key from name: 'Facturatie instellingen' → 'view_settings_facturatie_instellingen'
        if ($permission === null) {
            $permission = 'view_settings_' . \Illuminate\Support\Str::snake(str_replace(' ', '_', $name));
        }

        $baseName = trim(preg_replace('/\s*instellingen\s*/i', ' ', $name));
        $this->registerRolePermissions('Instellingen', [
            $permission => trim($baseName) . ' instellingen bekijken',
        ]);

        cms()->builder('settingPages', [
            $className => [
                'name' => $name,
                'description' => $description ?: 'Instellingen voor ' . str($name)->plural()->lower(),
                'icon' => $icon,
                'page' => $settingsPage,
                'permission' => $permission,
            ],
        ]);
    }

    /**
     * Access the IntegrationsDashboard registry. Useful for filament pages
     * and widgets that need to iterate all registered integrations.
     */
    public function integrationRegistry(): \Dashed\DashedCore\Integrations\IntegrationRegistry
    {
        return app(\Dashed\DashedCore\Integrations\IntegrationRegistry::class);
    }

    /**
     * Register a recommendation strategy. Lives in dashed-ecommerce-core so
     * dashed-core stays lazily coupled - we use a FQN string + class_exists
     * check so test stacks that don't have ecommerce-core installed don't
     * blow up.
     *
     * @param  object  $strategy  Must implement
     *   `Dashed\DashedEcommerceCore\Services\Recommendations\Strategies\RecommendationStrategy`.
     * @param  array<int, object>|null  $placements  Optional RecommendationPlacement enum cases.
     */
    public function registerRecommendationStrategy(object $strategy, ?array $placements = null): self
    {
        $registryClass = 'Dashed\\DashedEcommerceCore\\Services\\Recommendations\\RecommendationRegistry';

        if (! class_exists($registryClass)) {
            return $this;
        }

        app($registryClass)->register($strategy, $placements);

        return $this;
    }

    /**
     * Register an admin integration card on the IntegrationsDashboard.
     * Provider packages call this from `bootingPackage()`:
     *
     *     cms()->registerIntegration([
     *         'slug' => 'mollie',
     *         'label' => 'Mollie',
     *         'icon' => 'heroicon-o-credit-card',
     *         'category' => 'payment',
     *         'settings_page' => MollieSettingsPage::class,
     *         'health_check' => fn (?string $siteId) => IntegrationHealth::ok(),
     *         'docs_url' => 'https://docs.example.test/mollie',
     *         'package' => 'dashed-ecommerce-mollie',
     *     ]);
     */
    public function registerIntegration(array $cfg): self
    {
        $cfg['category'] = $cfg['category'] ?? 'other';

        if (! isset($cfg['permission']) && isset($cfg['settings_page'])) {
            $cfg['permission'] = $this->getSettingsPagePermission($cfg['settings_page']);
        }

        $definition = \Dashed\DashedCore\Integrations\IntegrationDefinition::fromArray($cfg);
        $this->integrationRegistry()->register($definition);

        return $this;
    }

    /**
     * Explicitly register a Customsetting key, claiming type, default, and
     * owning package. Always overrides any prior auto entry. Call from a
     * service provider's bootingPackage()/packageBooted() to flip a key
     * from "auto-registered (needs review)" to "explicitly registered" in
     * `dashed:settings:audit`.
     */
    public function registerSetting(
        string $key,
        string $type,
        mixed $default,
        string $package,
        ?string $label,
        ?string $description = null,
    ): self {
        app(\Dashed\DashedCore\Settings\SettingsRegistry::class)->register(
            key: $key,
            type: $type,
            default: $default,
            package: $package,
            label: $label,
            description: $description,
        );

        return $this;
    }

    public function getSettingsPagePermission(string $pageClass): ?string
    {
        foreach (static::$builders['settingPages'] ?? [] as $page) {
            if (($page['page'] ?? null) === $pageClass) {
                return $page['permission'] ?? null;
            }
        }

        return null;
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
        $css = file_get_contents(resource_path('css/app.css')) ?? '';

        preg_match_all(
            '/--color-([a-z0-9\-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/i',
            $css,
            $m
        );

        $colors = collect($m[1])
            ->combine($m[2]) // name => hex
            ->mapWithKeys(fn ($hex, $name) => [
                $name => RichEditor\TextColor::make(Str::headline($name), $hex, darkColor: $hex),
            ])
            ->all();

        $builder = $this->builder('editor')::make($name)
            ->label($label ?: $name)
            ->fileAttachmentsDisk('dashed')
            ->fileAttachmentsDirectory('editor')
            ->fileAttachmentsVisibility('public')
            ->floatingToolbars([
                'paragraph' => [
                    'bold', 'italic', 'subscript', 'superscript',
                ],
                'heading' => [
                    'h1', 'h2', 'h3',
                ],
                'table' => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell',
                    'tableToggleHeaderRow',
                    'tableDelete',
                ],
            ])
            ->toolbarButtons([
                'attachFiles',
                'mediaEmbed',
                'insertExternalVideo',
//                'htmlId',
//                'embed',
                'sourceCode',
                'blockquote',
                'bold',
                'bulletList',
                'codeBlock',
                'h1',
                'h2',
                'h3',
                'italic',
                'link',
                'orderedList',
                'textColor',
                'superscript',
                'subscript',
                'code',
                'horizontalRule',
                'table',
                'clearFormatting',
                'alignStart',
                'alignCenter',
                'alignEnd',
                'alignJustify',
                'grid',
                'gridDelete',
                'details',
            ])
            ->formatStateUsing(function ($state) {
                $nodes = $this->normalizeRichState($state);
                $state = [
                    'type' => 'doc',
                    'content' => $this->cleanForFilamentRich($nodes),
                ];

                return $state;
            })
            ->json()
            ->plugins(array_merge(cms()->builder('richEditorPlugins'), [
//                EmbedPlugin::make(),
//                FullScreenPlugin::make(),
                IdPlugin::make(), // Doesn't have a toolbar button
                LinkPlugin::make(), // Requires IdPlugin
                SourceCodePlugin::make(),
            ]))
            ->textColors($colors)
            ->customTextColors();

        return $builder;
    }

    public function normalizeRichState($state): array
    {
        if (blank($state)) {
            return [];
        }

        // 1) JSON-string → array
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            $state = is_array($decoded) ? $decoded : [];
        }

        // 2) Al een array van nodes? (numerieke keys)
        if (is_array($state) && Arr::isList($state) && isset($state[0]['type'])) {
            return $state; // klaar
        }

        // 3) Tiptap doc-vorm: ['type' => 'doc', 'content' => [...]]
        if (isset($state['type']) && $state['type'] === 'doc' && isset($state['content']) && is_array($state['content'])) {
            return $state['content'];
        }

        // 4) AWCodes/andere varianten met 'content' root
        if (isset($state['content']) && is_array($state['content'])) {
            return $state['content'];
        }

        // 5) Anders: empty (voorkomt "Undefined array key 'content'")
        return [];
    }

    public function cleanForFilamentRich(array $nodes): array
    {
        $keepNodeAttrs = function (array $node): array {
            $type = $node['type'] ?? null;
            $attrs = $node['attrs'] ?? [];

            $pick = fn (array $src, array $keys) => array_reduce($keys, function ($carry, $k) use ($src) {
                if (array_key_exists($k, $src)) {
                    $carry[$k] = $src[$k];
                }

                return $carry;
            }, []);

            switch ($type) {
                case 'heading':
                    // Filament v4 ondersteunt text alignment op headings
                    $node['attrs'] = $pick($attrs, ['level', 'textAlign', 'id']);

                    break;

                case 'paragraph':
                    // Alleen uitlijning bewaren
                    $node['attrs'] = $pick($attrs, ['textAlign']);
                    if (empty($node['attrs'])) {
                        unset($node['attrs']);
                    }

                    break;

                case 'image':
                    // Belangrijk: src NIET strippen, anders zie je het plaatje niet (ook niet in tables)
                    $node['attrs'] = $pick($attrs, ['src', 'alt', 'title', 'width', 'height']);

                    break;

                case 'table':
                    // Meestal geen attrs nodig, maar laat staan als er ooit iets komt
                    $node['attrs'] = $pick($attrs, []); // noop
                    if (empty($node['attrs'])) {
                        unset($node['attrs']);
                    }

                    break;

                case 'tableRow':
                case 'tableHeader':
                case 'tableCell':
                    // Bewaar cel-attrs die Tiptap gebruikt
                    $node['attrs'] = $pick($attrs, ['colspan', 'rowspan', 'colwidth']);
                    if (empty($node['attrs'])) {
                        unset($node['attrs']);
                    }

                    break;

                case 'externalVideo':
                case 'mediaEmbed':
                    // Jouw plugins gebruiken doorgaans deze attrs
                    $node['attrs'] = $pick($attrs, ['src', 'ratio', 'provider']);

                    break;

                case 'horizontalRule':
                case 'codeBlock':
                case 'blockquote':
                case 'bulletList':
                case 'orderedList':
                case 'listItem':
                case 'hardBreak':
                case 'text':
                    // Niks bijzonders nodig
                    if (isset($node['attrs'])) {
                        unset($node['attrs']);
                    }

                    break;

                default:
                    // Onbekende node-types: laat attrs met rust? Liever safe → strippen.
                    // Als je custom nodes hebt met attrs, voeg ze bovenin toe aan de switch.
                    if (isset($node['attrs'])) {
                        unset($node['attrs']);
                    }

                    break;
            }

            return $node;
        };

        return array_map(function ($node) use ($keepNodeAttrs) {
            // 1) Node-attrs whitelisten
            $node = $keepNodeAttrs($node);

            // 2) Marks filteren (inline styles weg)
            if (isset($node['marks']) && is_array($node['marks'])) {
                $node['marks'] = array_values(array_filter(
                    $node['marks'],
                    fn ($mark) => ($mark['type'] ?? '') !== 'textStyle'
                ));
                if (empty($node['marks'])) {
                    unset($node['marks']);
                }
            }

            // 3) Recurse door children
            if (isset($node['content']) && is_array($node['content'])) {
                $node['content'] = $this->cleanForFilamentRich($node['content']);
                if (empty($node['content'])) {
                    unset($node['content']);
                }
            }

            return $node;
        }, $nodes);
    }

    public function convertToHtml($content): string
    {
        if (! $content) {
            return '';
        }

        $html = RichEditor\RichContentRenderer::make($content)
            ->plugins(cms()->builder('richEditorPlugins'))
            ->toUnsafeHtml();

        return $this->addHeadingIdsToHtml($html);
    }

    protected function addHeadingIdsToHtml(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');

        // DOMDocument is soms drama met encoding + wrappers, dus we wrappen ff in een div
        $wrapped = '<div>' . $html . '</div>';

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Verzamel bestaande ids zodat we geen collisions krijgen
        $usedIds = [];
        foreach ($xpath->query('//*[@id]') as $el) {
            /** @var \DOMElement $el */
            $id = (string)$el->getAttribute('id');
            if ($id !== '') {
                $usedIds[$id] = true;
            }
        }

        // Pak alle headings
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

        foreach ($headings as $heading) {
            /** @var \DOMElement $heading */

            // Als er al een id is: laten staan
            if ($heading->hasAttribute('id') && trim((string)$heading->getAttribute('id')) !== '') {
                continue;
            }

            // Visible text (zonder HTML tags)
            $text = trim(preg_replace('/\s+/u', ' ', $heading->textContent ?? ''));

            if ($text === '') {
                continue;
            }

            // Slug maken (Laravel)
            $base = \Illuminate\Support\Str::slug($text);

            // Als slug leeg is (bijv alleen emoji/tekens), fallback
            if ($base === '') {
                $base = 'heading';
            }

            // Uniek maken
            $candidate = $base;
            $i = 2;
            while (isset($usedIds[$candidate])) {
                $candidate = $base . '-' . $i;
                $i++;
            }

            $heading->setAttribute('id', $candidate);
            $usedIds[$candidate] = true;
        }

        // Unwrap de outer div weer (we willen niet extra wrapper HTML teruggeven)
        $container = $dom->getElementsByTagName('div')->item(0);

        $result = '';
        if ($container) {
            foreach ($container->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }

            return $result;
        }

        return $html;
    }

    public function convertToArray($content): string|array
    {
        if (! $content) {
            return '';
        }

        return RichEditor\RichContentRenderer::make($content)
            ->plugins(cms()->builder('richEditorPlugins'))
            ->toArray();
    }
}
