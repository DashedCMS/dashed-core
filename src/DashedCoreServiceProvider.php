<?php

namespace Dashed\DashedCore;

use Livewire\Livewire;
use Illuminate\Support\Facades\Mail;
use App\Providers\AppServiceProvider;
use Dashed\DashedForms\Classes\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Dashed\DashedCore\Classes\Locales;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Package;
use Filament\Forms\Components\TextInput;
use Dashed\DashedCore\Commands\MigrateToV4;
use Illuminate\Console\Scheduling\Schedule;
use Filament\Forms\Components\Builder\Block;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Commands\InstallCommand;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Commands\SyncGoogleReviews;
use Dashed\DashedCore\Commands\CreateDefaultPages;
use Dashed\DashedCore\Commands\MigrateDatabaseToV4;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Commands\CreateVisitableModel;
use Dashed\DashedCore\Support\GoogleBusinessLocationsService;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Commands\RunUrlHistoryCheckCommand;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageStats;
use Dashed\DashedCore\Commands\ReplaceEditorStringsInFiles;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Classes\RichEditorPlugins\HtmlIdPlugin;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Dashed\DashedCore\Filament\Pages\Settings\AISettingsPage;
use Dashed\DashedCore\Livewire\Frontend\Search\SearchResults;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Livewire\Infolists\SEO\SEOScoreInfoList;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageGlobalStats;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Classes\RichEditorPlugins\MediaEmbedPlugin;
use Dashed\DashedCore\Classes\RichEditorPlugins\VideoEmbedPlugin;
use Dashed\DashedCore\Filament\Pages\Settings\SearchSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;
use Dashed\DashedCore\Livewire\Frontend\Protection\PasswordProtection;
use Dashed\DashedCore\Commands\AutomaticlyCreateAltTextsForAllMediaItems;

class DashedCoreServiceProvider extends PackageServiceProvider
{
    use GoogleBusinessLocationsService;

    public static string $name = 'dashed-core';

    public function bootingPackage()
    {
        $this->logProviderMemory('bootingPackage:start');
        Model::unguard();

        Livewire::component('notification.toastr', Toastr::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.forgot-password', ForgotPassword::class);
        Livewire::component('auth.reset-password', ResetPassword::class);
        Livewire::component('account.account', Account::class);
        //        Livewire::component('infolists.seo', SEOScoreInfoList::class);
        Livewire::component('search.search-results', SearchResults::class);
        Livewire::component('protection.password-protection', PasswordProtection::class);

        //Widgets
        Livewire::component('not-found-page-stats', NotFoundPageStats::class);
        Livewire::component('not-found-page-global-stats', NotFoundPageGlobalStats::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
            $schedule->command(RunUrlHistoryCheckCommand::class)->everyFifteenMinutes();
            $schedule->command(SyncGoogleReviews::class)->twiceDaily();
            $schedule->command(AutomaticlyCreateAltTextsForAllMediaItems::class)->hourly();
            //            $schedule->command(SeoScan::class)->daily();
        });

        if (! $this->app->environment('production')) {
            Mail::alwaysFrom('info@dashed.nl');
            Mail::alwaysTo('info@dashed.nl');
        }

        $builderBlockClasses = [];
        //        if (config('dashed-core.registerDefaultBuilderBlocks', true)) {
        //            $builderBlockClasses[] = 'builderBlocks';
        //        }

        $builderBlockClasses[] = 'defaultPageBuilderBlocks';

        cms()->builder('builderBlockClasses', [
            self::class => $builderBlockClasses,
        ]);

        cms()->builder('createDefaultPages', [
            self::class => 'createDefaultPages',
        ]);

        cms()->builder('publishOnUpdate', [
            'dashed-core-config',
            'dashed-core-assets',
        ]);

        cms()->builder('blockDisabledForCache', [
            'reset-password-block',
            'forgot-password-block',
            'login-block',
            'account-block',
            'password-protection-block',
        ]);

        cms()->builder('plugins', [
            new DashedCorePlugin(),
        ]);

        cms()->builder('richEditorPlugins', [
            VideoEmbedPlugin::make(),
            MediaEmbedPlugin::make(),
//            HtmlIdPlugin::make(),
        ]);

        $this->logProviderMemory('bootingPackage:end');
    }

    public static function builderBlocks()
    {
        $defaultBlocks = [
            Block::make('header')
                ->label('Header')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('toptitle')
                        ->label('Top title'),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                ]),
            Block::make('header-2')
                ->label('Header 2')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                ]),
            Block::make('header-3')
                ->label('Header 3')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle')
                        ->label('Sub titel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding'),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('subtitle')
                                ->label('Subtitel')
                                ->required(),
                            TextInput::make('icon')
                                ->label('Icoon')
                                ->helperText('Lucide icons: https://lucide.dev/icons/, just paste the svg code here')
                                ->required(),
                        ]),
                ]),
            Block::make('header-4')
                ->label('Header 4')
                ->schema([
                    self::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle')
                        ->label('Subtitel'),
                    self::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Achtergrond afbeelding', isImage: true),
                    mediaHelper()->field('image-2', 'Afbeelding 2', isImage: true),
                    mediaHelper()->field('image-3', 'Afbeelding 3', isImage: true),
                    mediaHelper()->field('image-4', 'Afbeelding 4', isImage: true),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            linkHelper()->field('url', true, 'URL'),
                            TextInput::make('icon')
                                ->label('Icoon')
                                ->helperText('Lucide icons: https://lucide.dev/icons/, just paste the svg code here')
                                ->required(),
                        ]),
                ]),
            Block::make('spacer')
                ->label('Spacer')
                ->schema([]),
            Block::make('small-spacer')
                ->label('Kleine spacer')
                ->schema([]),
            Block::make('content-with-image')
                ->label('Content with image')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    TextInput::make('subtitle')
                        ->label('Subtitel'),
                    Toggle::make('image-left')
                        ->label('Afbeelding links'),
                    cms()->editorField('content', 'Content'),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                ]),
            Block::make('content')
                ->label('Content')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Toggle::make('full-width')
                        ->label('Volledige breedte'),
                    cms()->editorField('content', 'Content')
                        ->required(),
                ]),
            Block::make('contact-form')
                ->label('Contact form')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('content', 'Content'),
                    Toggle::make('show_side_info')
                        ->default(true),
                    Forms::formSelecter(),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                ]),
            Block::make('usps-with-icon')
                ->label('USPs met iconen')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Repeater::make('usps')
                        ->label('USPs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('subtitle', 'Subtitel')
                                ->required(),
                            IconPicker::make('icon')
                                ->label('Icoon')
                                ->required(),
                        ]),
                ]),
            Block::make('image-blocks-with-info')
                ->label('Afbeelding blokken met info')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Repeater::make('blocks')
                        ->label('Blokken')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            TextInput::make('subtitle')
                                ->label('Subtitel')
                                ->required(),
                            mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                            AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                        ]),
                ]),
            Block::make('logo-cloud')
                ->label('Logo cloud')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    Repeater::make('logos')
                        ->label('Logos')
                        ->minItems(0)
                        ->maxItems(100)
                        ->schema([
                            mediaHelper()->field('image', 'Afbeelding', required: true, isImage: true),
                            linkHelper()->field('url', true),
                        ]),
                ]),
            Block::make('team')
                ->label('Team')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    cms()->editorField('subtitle', 'Subtitel'),

                    Repeater::make('team')
                        ->label('Team')
                        ->required()
                        ->minItems(1)
                        ->cloneable()
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required(),
                            TextInput::make('function')
                                ->required()
                                ->label('Functie'),
                            mediaHelper()->field('image', 'Afbeelding', required: true, isImage: true),
                            mediaHelper()->field('image-2', 'Afbeelding 2', required: true, isImage: true),
                        ]),
                ]),
            Block::make('faq')
                ->label('FAQ')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel'),
                    TextInput::make('subtitle')
                        ->label('Subtitel'),
                    TextInput::make('columns')
                        ->numeric()
                        ->label('Aantal kolommen'),
                    Repeater::make('faqs')
                        ->label('FAQs')
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required(),
                            cms()->editorField('content', 'Content')
                                ->required(),
                        ]),
                ]),
            Block::make('maps-embed')
                ->label('Maps embed')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                ]),
            Block::make('html')
                ->label('HTML')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Textarea::make('html')
                        ->label('HTML')
                        ->required()
                        ->rows(5),
                ]),
            Block::make('media')
                ->label('Afbeelding / video')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    mediaHelper()->field('media', 'Afbeelding / video', isImage: true, required: true),
                    TextInput::make('max_width_number')
                        ->label('Max breedte')
                        ->default(100)
                        ->integer()
                        ->minValue(0)
                        ->maxValue(10000),
                    Select::make('max_width_type')
                        ->label('Max breedte')
                        ->default('%')
                        ->options([
                            'px' => 'px',
                            '%' => '%',
                        ]),
                    Select::make('align')
                        ->label('Uitlijning')
                        ->default('center')
                        ->options([
                            'center' => 'Midden',
                            'left' => 'Links',
                            'right' => 'Rechts',
                        ]),
                ]),
            Block::make('search-results-block')
                ->label('Zoekresultaten')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                ]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public static function defaultPageBuilderBlocks()
    {
        $defaultBlocks = [
            Block::make('account-block')
                ->label('Account')
                ->schema([]),
            Block::make('login-block')
                ->label('Login')
                ->schema([]),
            Block::make('forgot-password-block')
                ->label('Wachtwoord vergeten')
                ->schema([]),
            Block::make('reset-password-block')
                ->label('Reset wachtwoord')
                ->schema([]),
            Block::make('password-protection-block')
                ->label('Wachtwoord beveiliging voor pagina\'s')
                ->schema([]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public function configurePackage(Package $package): void
    {
        $this->logProviderMemory('configurePackage:start');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        //        $this->loadViewsFrom(__DIR__ . '/../resources/views/frontend', 'dashed-core');

        $this->publishes([
            __DIR__ . '/../resources/templates' => resource_path('views/' . config('dashed-core.site_theme', 'dashed')),
            __DIR__ . '/../resources/component-templates' => resource_path('views/components'),
        ], 'dashed-templates');

        cms()->registerSettingsPage(GeneralSettingsPage::class, 'Algemeen', 'cog', 'Algemene informatie van de website');
        cms()->registerSettingsPage(AccountSettingsPage::class, 'Account', 'user', 'Account instellingen van de website');
        cms()->registerSettingsPage(SEOSettingsPage::class, 'SEO', 'identification', 'SEO van de website');
        cms()->registerSettingsPage(ImageSettingsPage::class, 'Afbeelding', 'photo', 'Afbeelding van de website');
        cms()->registerSettingsPage(CacheSettingsPage::class, 'Cache', 'photo', 'Cache van de website');
        cms()->registerSettingsPage(SearchSettingsPage::class, 'Search', 'magnifying-glass', 'Zoek instellingen van de website');
        cms()->registerSettingsPage(AISettingsPage::class, 'AI', 'magnifying-glass', 'AI instellingen van de website');

        $package
            ->name(static::$name)
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
//                'filament-tiptap-editor',
                'filesystems',
                'file-manager',
                'livewire',
                'laravellocalization',
                'flare',
                'dashed-core',
//                'seo',
                'activitylog',
            ])
            ->hasRoutes([
                'frontend',
            ])
            ->hasViews()
            ->hasAssets()
            ->hasCommands([
                CreateAdminUser::class,
                InstallCommand::class,
                UpdateCommand::class,
                InvalidatePasswordResetTokens::class,
                CreateSitemap::class,
                CreateVisitableModel::class,
                RunUrlHistoryCheckCommand::class,
                SyncGoogleReviews::class,
                CreateDefaultPages::class,
                ReplaceEditorStringsInFiles::class,
                AutomaticlyCreateAltTextsForAllMediaItems::class,
                MigrateToV4::class,
                MigrateDatabaseToV4::class,
            ]);

        $this->logProviderMemory('configurePackage:end');
    }

    public static function createDefaultPages(): void
    {
        if (! \Dashed\DashedPages\Models\Page::where('is_home', 1)->count()) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Home');
                $page->setTranslation('slug', $locale, 'home');
            }
            $page->is_home = 1;
            $page->save();

            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Contact');
                $page->setTranslation('slug', $locale, 'contact');
            }
            $page->save();
        }

        if (! \Dashed\DashedCore\Models\Customsetting::get('search_page_id')) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Zoek resultaten');
                $page->setTranslation('slug', $locale, 'zoeken');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                            'title' => 'Zoekresultaten',
                        ],
                        'type' => 'search-results-block',
                    ],
                ]);
            }
            $page->save();
            \Dashed\DashedCore\Models\Customsetting::set('search_page_id', $page->id);
        }

        if (! \Dashed\DashedCore\Models\Customsetting::get('login_page_id')) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Login');
                $page->setTranslation('slug', $locale, 'login');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'login-block',
                    ],
                ]);
            }
            $page->save();

            \Dashed\DashedCore\Models\Customsetting::set('login_page_id', $page->id);
        }

        if (! \Dashed\DashedCore\Models\Customsetting::get('account_page_id')) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Account');
                $page->setTranslation('slug', $locale, 'account');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'account-block',
                    ],
                ]);
            }
            $page->save();

            \Dashed\DashedCore\Models\Customsetting::set('account_page_id', $page->id);

            $page->metadata()->create([
                'noindex' => true,
            ]);
        }

        if (! \Dashed\DashedCore\Models\Customsetting::get('forgot_password_page_id')) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Wachtwoord vergeten');
                $page->setTranslation('slug', $locale, 'wachtwoord-vergeten');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'forgot-password-block',
                    ],
                ]);
            }
            $page->save();

            \Dashed\DashedCore\Models\Customsetting::set('forgot_password_page_id', $page->id);
        }

        if (! \Dashed\DashedCore\Models\Customsetting::get('reset_password_page_id')) {
            $page = new \Dashed\DashedPages\Models\Page();
            foreach (Locales::getActivatedLocalesFromSites() as $locale) {
                $page->setTranslation('name', $locale, 'Reset wachtwoord');
                $page->setTranslation('slug', $locale, 'reset-wachtwoord');
                $page->setTranslation('content', $locale, [
                    [
                        'data' => [
                            'in_container' => true,
                            'top_margin' => true,
                            'bottom_margin' => true,
                        ],
                        'type' => 'reset-password-block',
                    ],
                ]);
            }
            $page->save();

            \Dashed\DashedCore\Models\Customsetting::set('reset_password_page_id', $page->id);

            $page->metadata()->create([
                'noindex' => true,
            ]);
        }
    }
}
