<?php

namespace Dashed\DashedCore\Filament\Resources\AbTestResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Models\AbTest;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Classes\AbTests\AbTests;
use Dashed\DashedCore\Classes\AbTests\AbTestApplier;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;
use Dashed\DashedCore\Filament\Resources\AbTestResource;

class EditAbTest extends EditRecord
{
    protected static string $resource = AbTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label(__('Test starten'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (AbTest $record) => $record->status === AbTest::STATUS_DRAFT)
                ->requiresConfirmation()
                ->modalDescription(__('Vanaf nu ziet een deel van de bezoekers variant B. Variant B en het doel liggen dan vast; wijzig ze liever niet meer tijdens de test.'))
                ->action(function (AbTest $record): void {
                    if ($record->variantBData() === null || ! AbTestApplier::locate($record)) {
                        Notification::make()
                            ->title(__('De test kan niet starten: variant B is leeg of het blok staat niet (meer) op de pagina'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->update(['status' => AbTest::STATUS_RUNNING, 'started_at' => $record->started_at ?? now()]);
                    static::flush($record);
                    Notification::make()->title(__('De test loopt'))->success()->send();
                }),
            Action::make('pause')
                ->label(__('Pauzeren'))
                ->icon('heroicon-o-pause')
                ->color('gray')
                ->visible(fn (AbTest $record) => $record->isRunning())
                ->action(function (AbTest $record): void {
                    $record->update(['status' => AbTest::STATUS_DRAFT]);
                    static::flush($record);
                }),
            Action::make('applyWinner')
                ->label(__('Winnaar toepassen'))
                ->icon('heroicon-o-trophy')
                ->visible(fn (AbTest $record) => $record->status !== AbTest::STATUS_FINISHED)
                ->schema(fn (AbTest $record) => [
                    Radio::make('winner')
                        ->label(__('Welke variant blijft staan?'))
                        ->options([
                            'a' => __('Variant A (het blok blijft zoals het is)'),
                            'b' => __('Variant B (vervangt het blok op de pagina)'),
                        ])
                        ->default(AbTests::statistics($record)['leader'] ?? 'a')
                        ->required(),
                ])
                ->modalDescription(__('Rondt de test af. Bij variant B wordt het blok op de pagina vervangen door variant B; de tellers blijven bewaard.'))
                ->action(function (AbTest $record, array $data): void {
                    AbTestApplier::apply($record, $data['winner']);
                    Notification::make()->title(__('De winnaar is toegepast'))->success()->send();
                    $this->refreshFormData(['status_label']);
                }),
            DeleteAction::make()
                ->before(function (AbTest $record): void {
                    // Het blok weer los van de test, zodat het gewoon variant A blijft.
                    if ($record->status !== AbTest::STATUS_FINISHED) {
                        AbTestApplier::apply($record, 'a');
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->isRunning()) {
            static::flush($this->record);
        }
    }

    protected static function flush(AbTest $record): void
    {
        if ($record->model) {
            CacheInvalidator::forModel($record->model);
        }
    }
}
