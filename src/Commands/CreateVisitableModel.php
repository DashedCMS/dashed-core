<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Facades\File;
use Binafy\LaravelStub\Facades\LaravelStub;

class CreateVisitableModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:create-visitable-model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a visitable model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating visitable model... '.$this->argument('name'));

        $single = Pluralizer::singular(str($this->argument('name'))->ucfirst());
        $plural = Pluralizer::plural(str($this->argument('name'))->ucfirst());

        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/model.stub')
            ->to('app/Models')
            ->name($single)
            ->ext('php')
            ->replaces([
                'NAMESPACE' => 'App\Models',
                'CLASS' => $single,
                'TABLE' => str($plural)->lower(),
            ])
            ->generate();

        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/migration.stub')
            ->to('database/migrations')
            ->name(now()->format('Y_m_d').'_'.now()->secondsSinceMidnight().'_create_'.str($plural)->lower().'_table')
            ->ext('php')
            ->replaces([
                'CLASS' => str($plural)->lower(),
            ])
            ->generate();

        if (! File::exists(base_path('resources/views/dashed/'.str($single)->lower()))) {
            File::makeDirectory(base_path('resources/views/dashed/'.str($single)->lower()), 0755, true);
        }
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/page.stub')
            ->to('resources/views/dashed/'.str($single)->lower())
            ->name('show.blade')
            ->ext('php')
            ->replaces([
                'CLASS' => str($single)->lower(),
            ])
            ->generate();

        if (! File::exists(base_path('app/Filament/Resources'))) {
            File::makeDirectory(base_path('app/Filament/Resources'), 0755, true);
        }
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/filament-resource.stub')
            ->to('app/Filament/Resources')
            ->name($single.'Resource')
            ->ext('php')
            ->replaces([
                'CLASS' => $single.'Resource',
                'MODELCLASS' => $single,
                'SINGLE' => $single,
                'SINGLELOW' => str($single)->lower(),
                'PLURAL' => $single,
                'TABLE' => str($plural)->lower(),
            ])
            ->generate();

        if (! File::exists(base_path('app/Filament/Resources/'.$single.'Resource/Pages'))) {
            File::makeDirectory(base_path('app/Filament/Resources/'.$single.'Resource/Pages'), 0755, true);
        }
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/filament-list.stub')
            ->to('app/Filament/Resources/'.$single.'Resource/Pages')
            ->name('List'.$plural)
            ->ext('php')
            ->replaces([
                'CLASS' => $single.'Resource',
                'MODELCLASS' => $single,
                'SINGLE' => $single,
                'SINGLELOW' => str($single)->lower(),
                'PLURAL' => $single,
            ])
            ->generate();
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/filament-create.stub')
            ->to('app/Filament/Resources/'.$single.'Resource/Pages')
            ->name('Create'.$single)
            ->ext('php')
            ->replaces([
                'CLASS' => $single.'Resource',
                'MODELCLASS' => $single,
                'SINGLE' => $single,
                'SINGLELOW' => str($single)->lower(),
                'PLURAL' => $single,
            ])
            ->generate();
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/filament-edit.stub')
            ->to('app/Filament/Resources/'.$single.'Resource/Pages')
            ->name('Edit'.$single)
            ->ext('php')
            ->replaces([
                'CLASS' => $single.'Resource',
                'MODELCLASS' => $single,
                'SINGLE' => $single,
                'SINGLELOW' => str($single)->lower(),
                'PLURAL' => $single,
            ])
            ->generate();

        if (! File::exists(base_path('app/Filament/Pages'))) {
            File::makeDirectory(base_path('app/Filament/Pages'), 0755, true);
        }
        LaravelStub::from(__DIR__.'/../Stubs/VisitableModel/settings-page.stub')
            ->to('app/Filament/Pages')
            ->name($single.'SettingsPage')
            ->ext('php')
            ->replaces([
                'CLASS' => $single,
                'SINGLE' => $single,
                'SINGLELOW' => str($single)->lower(),
                'PLURAL' => $single,
            ])
            ->generate();

        $this->info('Visitable model created successfully.');
        $this->info('Dont forget to register the settings page and visitable model in the AppPanelProvider & AppServiceProvider');
    }
}
