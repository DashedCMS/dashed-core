<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\BulkEditRun;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Classes\Changes\BulkEditor;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Dashed\DashedCore\Classes\Changes\ChangeFieldsClass;
use Dashed\DashedCore\Filament\Resources\BulkEditRunResource\Pages\ListBulkEditRuns;

/**
 * Overzicht van bulkbewerkingen, met per run de wijzigingen en
 * "Ongedaan maken".
 */
class BulkEditRunResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = BulkEditRun::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 91;

    protected static ?string $navigationLabel = 'Bulkbewerkingen';

    protected static ?string $label = 'Bulkbewerking';

    protected static ?string $pluralLabel = 'Bulkbewerkingen';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Tijd'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('model_type')
                    ->label(__('Soort'))
                    ->formatStateUsing(fn (string $state) => ChangeFields::modelLabel($state)),
                TextColumn::make('changes')
                    ->label(__('Wijziging'))
                    ->state(fn (BulkEditRun $record) => $record->summary())
                    ->wrap(),
                TextColumn::make('changed')
                    ->label(__('Gewijzigd'))
                    ->state(fn (BulkEditRun $record) => __(':gewijzigd van :totaal', ['gewijzigd' => $record->changed, 'totaal' => $record->total])),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => BulkEditRun::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state) => BulkEditRun::statusColors()[$state] ?? 'gray')
                    ->description(fn (BulkEditRun $record) => collect([
                        $record->failed ? __(':aantal mislukt', ['aantal' => $record->failed]) : null,
                        $record->undo_skipped ? __(':aantal records deels niet teruggezet (sindsdien opnieuw gewijzigd)', ['aantal' => $record->undo_skipped]) : null,
                        $record->error,
                    ])->filter()->implode(' ')),
                TextColumn::make('user.name')
                    ->label(__('Door'))
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->multiple()
                    ->options(BulkEditRun::statusLabels()),
            ])
            ->recordActions([
                Action::make('items')
                    ->label(__('Wijzigingen'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('Wijzigingen in deze bulkbewerking'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Sluiten'))
                    ->schema(fn (BulkEditRun $record) => [
                        \Filament\Schemas\Components\Html::make(static::itemsHtml($record)),
                    ]),
                Action::make('undo')
                    ->label(__('Ongedaan maken'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__('Zet de oude waarden terug. Velden die sindsdien opnieuw gewijzigd zijn, blijven staan.'))
                    ->visible(fn (BulkEditRun $record) => $record->canBeUndone() && (auth()->user()?->can('edit_bulk_edit_run') ?? false))
                    ->action(function (BulkEditRun $record) {
                        $run = BulkEditor::startUndo($record);

                        if ($run->status === BulkEditRun::STATUS_UNDONE) {
                            Notification::make()
                                ->title(__('Bulkbewerking ongedaan gemaakt'))
                                ->body($run->undo_skipped ? __(':aantal records deels niet teruggezet (sindsdien opnieuw gewijzigd)', ['aantal' => $run->undo_skipped]) : null)
                                ->success()
                                ->send();
                        } elseif ($run->status === BulkEditRun::STATUS_COMPLETED && $run->error) {
                            Notification::make()->title(__('Dat lukte niet'))->body($run->error)->danger()->send();
                        } else {
                            Notification::make()->title(__('Ongedaan maken gestart op de achtergrond'))->success()->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBulkEditRuns::route('/'),
        ];
    }

    protected static function itemsHtml(BulkEditRun $run): HtmlString
    {
        $class = ChangeFieldsClass::resolve($run->model_type);
        $fields = ChangeFields::for($class);
        $items = $run->items()->orderBy('id')->limit(200)->get();
        $records = class_exists($class)
            ? $class::query()->whereKey($items->pluck('model_id')->all())->get()->keyBy(fn (Model $model) => (string) $model->getKey())
            : collect();

        $html = '<style>.dbe-preview{width:100%;border-collapse:collapse;font-size:.875rem}.dbe-preview th,.dbe-preview td{text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgba(127,127,127,.25);vertical-align:top}.dbe-old{text-decoration:line-through;opacity:.6}.dbe-new{font-weight:600}</style>';
        $html .= '<table class="dbe-preview"><thead><tr><th>' . e(__('Record')) . '</th><th>' . e(__('Veld')) . '</th><th>' . e(__('Oud')) . '</th><th>' . e(__('Nieuw')) . '</th></tr></thead><tbody>';

        foreach ($items as $item) {
            $first = true;

            foreach ($item->new ?? [] as $key => $new) {
                $field = $fields[$key] ?? null;
                $skipped = in_array($key, $item->skipped ?? [], true);
                $html .= '<tr><td>' . ($first ? e(ChangeFields::recordLabel($records->get($item->model_id), $item->model_id)) : '') . '</td>'
                    . '<td>' . e($field?->getLabel() ?? $key) . ($skipped ? ' <em>(' . e(__('niet teruggezet')) . ')</em>' : '') . '</td>'
                    . '<td class="dbe-old">' . e($field ? $field->display($item->old[$key] ?? null) : json_encode($item->old[$key] ?? null)) . '</td>'
                    . '<td class="dbe-new">' . e($field ? $field->display($new) : json_encode($new)) . '</td></tr>';
                $first = false;
            }
        }

        $html .= '</tbody></table>';

        if ($run->changed > $items->count()) {
            $html .= '<p>' . e(__('En nog :aantal records.', ['aantal' => $run->changed - $items->count()])) . '</p>';
        }

        return new HtmlString($html);
    }
}
