<?php

namespace Dashed\DashedCore;

use Livewire\Livewire;
use Illuminate\Support\Facades\Mail;
use App\Providers\AppServiceProvider;
use Dashed\DashedForms\Classes\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Package;
use Filament\Forms\Components\TextInput;
use Illuminate\Console\Scheduling\Schedule;
use Filament\Forms\Components\Builder\Block;
use Dashed\DashedCore\Commands\CreateSitemap;
use Dashed\DashedCore\Commands\UpdateCommand;
use Dashed\DashedCore\Commands\InstallCommand;
use Dashed\DashedCore\Commands\CreateAdminUser;
use Dashed\DashedCore\Commands\SyncGoogleReviews;
use Dashed\DashedCore\Livewire\Frontend\Auth\Login;
use Dashed\DashedCore\Commands\CreateVisitableModel;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedCore\Livewire\Frontend\Account\Account;
use Dashed\DashedCore\Commands\RunUrlHistoryCheckCommand;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageStats;
use Dashed\DashedCore\Livewire\Frontend\Auth\ResetPassword;
use Dashed\DashedCore\Livewire\Frontend\Auth\ForgotPassword;
use Dashed\DashedCore\Livewire\Frontend\Notification\Toastr;
use Dashed\DashedCore\Commands\InvalidatePasswordResetTokens;
use Dashed\DashedCore\Filament\Pages\Settings\SEOSettingsPage;
use Dashed\DashedCore\Livewire\Infolists\SEO\SEOScoreInfoList;
use Dashed\DashedCore\Filament\Widgets\NotFoundPageGlobalStats;
use Dashed\DashedCore\Filament\Pages\Settings\CacheSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\ImageSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\AccountSettingsPage;
use Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage;

class DashedCoreServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-core';

    public function bootingPackage()
    {
        Model::unguard();

        Livewire::component('notification.toastr', Toastr::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.forgot-password', ForgotPassword::class);
        Livewire::component('auth.reset-password', ResetPassword::class);
        Livewire::component('account.account', Account::class);
        Livewire::component('infolists.seo', SEOScoreInfoList::class);

        //Widgets
        Livewire::component('not-found-page-stats', NotFoundPageStats::class);
        Livewire::component('not-found-page-global-stats', NotFoundPageGlobalStats::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(CreateSitemap::class)->daily();
            $schedule->command(InvalidatePasswordResetTokens::class)->everyFifteenMinutes();
            $schedule->command(RunUrlHistoryCheckCommand::class)->everyFifteenMinutes();
            $schedule->command(SyncGoogleReviews::class)->twiceDaily();
            //            $schedule->command(SeoScan::class)->daily();
        });

        if (! $this->app->environment('production')) {
            Mail::alwaysFrom('info@dashed.nl');
            Mail::alwaysTo('info@dashed.nl');
        }

        $builderBlockClasses = [];
        if (config('dashed-core.registerDefaultBuilderBlocks', true)) {
            $builderBlockClasses[] = 'builderBlocks';
        }

        $builderBlockClasses[] = 'defaultPageBuilderBlocks';

        cms()->builder('builderBlockClasses', [
            self::class => $builderBlockClasses,
        ]);

        cms()->builder('publishOnUpdate', [
            'dashed-core-config',
            'dashed-core-assets',
        ]);
    }

    public static function builderBlocks()
    {
        $defaultBlocks = [
            Block::make('hero')
                ->label('Hero')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('toptitle')
                        ->label('Top title'),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    TextInput::make('subtitle')
                        ->label('Sub titel')
                        ->required(),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding', required: true, isImage: true),
                ]),
            Block::make('header')
                ->label('Header')
                ->schema([
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    Textarea::make('subtitle')
                        ->label('Subtitel'),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                    AppServiceProvider::getDefaultBlockFields(),
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
                    TiptapEditor::make('content')
                        ->label('Content'),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                    AppServiceProvider::getButtonRepeater('buttons', 'Buttons'),
                ]),
            Block::make('content')
                ->label('Content')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Toggle::make('full-width')
                        ->label('Afbeelding links'),
                    TiptapEditor::make('content')
                        ->label('Content')
                        ->required(),
                ]),
            Block::make('contact-form')
                ->label('Contact form')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    TiptapEditor::make('content')
                        ->label('Content'),
                    Toggle::make('show_side_info')
                        ->default(true),
                    Forms::formSelecter(),
                    mediaHelper()->field('image', 'Afbeelding', isImage: true, required: true),
                ]),
            Block::make('media')
                ->label('Afbeelding / video')
                ->schema([
                    mediaHelper()->field('media', 'Afbeelding of video', required: true),
                    AppServiceProvider::getDefaultBlockFields(),
                    TextInput::make('max_width_number')
                        ->label('Max breedte nummer')
                        ->default(500)
                        ->integer()
                        ->minValue(0)
                        ->maxValue(10000),
                    Select::make('max_width_type')
                        ->label('Max breedte type')
                        ->default('px')
                        ->options([
                            'px' => 'px',
                            '%' => '%',
                        ]),
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
                            TiptapEditor::make('subtitle')
                                ->label('Subtitel')
                                ->required(),
                            TextInput::make('icon')
                                ->label('Icoon')
                                ->helperText('Lucide icons')
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
                    TextInput::make('title')
                        ->label('Titel')
                        ->required(),
                    TiptapEditor::make('subtitle')
                        ->label('Subtitel'),

                    Repeater::make('team')
                        ->label('Team')
                        ->required()
                        ->minItems(1)
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
            Block::make('maps-embed')
                ->label('Maps embed')
                ->schema([]),
            Block::make('html')
                ->label('HTML')
                ->schema([
                    AppServiceProvider::getDefaultBlockFields(),
                    Textarea::make('html')
                        ->label('HTML')
                        ->required()
                        ->rows(5),
                ]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public static function defaultPageBuilderBlocks()
    {
        $defaultBlocks = [
            Block::make('account')
                ->label('Account')
                ->schema([]),
            Block::make('login')
                ->label('Login')
                ->schema([]),
            Block::make('forgot-password')
                ->label('Wachtwoord vergeten')
                ->schema([]),
            Block::make('reset-password')
                ->label('Reset wachtwoord')
                ->schema([]),
        ];

        cms()
            ->builder('blocks', $defaultBlocks);
    }

    public function register()
    {
        return parent::register(); // TODO: Change the autogenerated stub
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        //        $this->loadViewsFrom(__DIR__ . '/../resources/views/frontend', 'dashed-core');

        $this->publishes([
            __DIR__ . '/../resources/templates' => resource_path('views/' . env('SITE_THEME', 'dashed')),
            __DIR__ . '/../resources/component-templates' => resource_path('views/components'),
        ], 'dashed-templates');

        cms()->builder(
            'settingPages',
            [
                'general' => [
                    'name' => 'Algemeen',
                    'description' => 'Algemene informatie van de website',
                    'icon' => 'cog',
                    'page' => GeneralSettingsPage::class,
                ],
                'account' => [
                    'name' => 'Account',
                    'description' => 'Account instellingen van de website',
                    'icon' => 'user',
                    'page' => AccountSettingsPage::class,
                ],
                'seo' => [
                    'name' => 'SEO',
                    'description' => 'SEO van de website',
                    'icon' => 'identification',
                    'page' => SEOSettingsPage::class,
                ],
                'image' => [
                    'name' => 'Afbeelding',
                    'description' => 'Afbeelding van de website',
                    'icon' => 'photo',
                    'page' => ImageSettingsPage::class,
                ],
                'cache' => [
                    'name' => 'Cache',
                    'description' => 'Cache van de website',
                    'icon' => 'photo',
                    'page' => CacheSettingsPage::class,
                ],
            ]
        );

        $package
            ->name(static::$name)
            ->hasConfigFile([
                'filament',
                'filament-spatie-laravel-translatable-plugin',
                'filament-forms-tinyeditor',
                'filament-tiptap-editor',
                'filesystems',
                'file-manager',
                'livewire',
                'laravellocalization',
                'flare',
                'dashed-core',
                'seo',
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
            ]);
    }
}
