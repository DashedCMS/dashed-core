<?php

namespace Qubiqx\QcommerceCore\Filament\Resources\MenuItemResource\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Query\Builder;
use Qubiqx\QcommerceCore\Filament\Resources\MenuResource;
use Filament\Resources\Pages\Page;

class ListMenuItem extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    public $record;

    protected static string $resource = MenuResource::class;
    protected static string $view = 'qcommerce-core::forms.pages.view-form';

    public function getTableSortColumn(): ?string
    {
        return 'viewed';
    }

    public function mount($record): void
    {
        $this->record = $this->getRecord($record);
    }

    protected function getTableQuery(): Builder
    {
        return $this->record->menuItems()->getQuery();
    }

    protected function getTitle(): string
    {
        return "Menuitems voor {$this->record->name}";
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'order';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'ASC';
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Naam')
                ->sortable()
                ->getStateUsing(fn ($record) => $record->name())
                ->searchable(),
            TextColumn::make('url')
                ->label('URL')
                ->getStateUsing(fn ($record) => str_replace(url('/'), '', $record->getUrl())),
            TextColumn::make('site_ids')
                ->label('Sites')
                ->getStateUsing(fn ($record) => implode(' | ', $record->site_ids)),
        ];
    }

//    protected function getTableActions(): array
//    {
//        return [
//            LinkAction::make('Bewerk')
//                ->url(fn (MenuItem $record): string => route('filament.resources.menu-items.edit', [$record->form->id, $record])),
//        ];
//    }
}
