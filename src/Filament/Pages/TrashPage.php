<?php

namespace Dashed\DashedCore\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Trash\Trash;

class TrashPage extends Page
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;

    protected static ?string $slug = 'prullenbak';

    protected static ?string $title = 'Prullenbak';

    protected static ?string $navigationLabel = 'Prullenbak';

    protected static string | \UnitEnum | null $navigationGroup = 'Systeem';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trash';

    protected static ?int $navigationSort = 90;

    protected string $view = 'dashed-core::pages.trash';

    #[Url]
    public ?string $type = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('view_trash');
    }

    public function items(): Collection
    {
        return Trash::items($this->type ?: null);
    }

    /**
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return collect(Trash::types())->map(fn ($meta) => $meta['label'])->all();
    }

    public function restore(string $type, string $id): void
    {
        $model = Trash::restore($type, $id);

        if (! $model) {
            return;
        }

        Notification::make()
            ->title(__(':item is teruggezet', ['item' => Trash::title($model)]))
            ->success()
            ->actions(array_filter([
                ($url = Trash::editUrl($model)) ? \Filament\Actions\Action::make('open')->label(__('Openen'))->url($url) : null,
            ]))
            ->send();
    }

    public function forceDelete(string $type, string $id): void
    {
        $model = Trash::find($type, $id);

        if ($model && Trash::forceDelete($type, $id)) {
            Notification::make()
                ->title(__(':item is definitief verwijderd', ['item' => Trash::title($model)]))
                ->success()
                ->send();
        }
    }
}
