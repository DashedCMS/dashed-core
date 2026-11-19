<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Dashed\DashedCore\Models\PageTemplate;
use Dashed\DashedCore\Classes\Editor\TemplateBlocks;

/**
 * "Nieuw vanuit sjabloon" op een lijstpagina: maakt meteen een record met de
 * blokken van het sjabloon en opent de bewerkpagina. Werkt voor elke
 * resource met een vertaalbaar of gewoon `content`-veld.
 */
class CreateFromTemplateAction
{
    public static function make(): Action
    {
        return Action::make('createFromPageTemplate')
            ->label(__('Nieuw vanuit sjabloon'))
            ->icon('heroicon-o-rectangle-stack')
            ->color('gray')
            ->visible(fn ($livewire) => static::canUse($livewire) && TemplateBlocks::options($livewire::getResource()::getModel()) !== [])
            ->schema(fn ($livewire) => [
                Select::make('template')
                    ->label(__('Sjabloon'))
                    ->options(TemplateBlocks::options($livewire::getResource()::getModel()))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->label(__('Naam'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data, $livewire) {
                $class = $livewire::getResource()::getModel();
                $template = PageTemplate::query()->forModel($class)->findOrFail($data['template']);
                $model = TemplateBlocks::createModel($class, $template, $data['name'], $livewire->activeLocale ?? app()->getLocale());

                return redirect($livewire::getResource()::getUrl('edit', ['record' => $model]));
            });
    }

    protected static function canUse($livewire): bool
    {
        return method_exists($livewire, 'getResource') && $livewire::getResource()::canCreate();
    }
}
