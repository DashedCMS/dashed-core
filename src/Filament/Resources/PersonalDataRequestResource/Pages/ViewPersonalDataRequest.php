<?php

namespace Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Dashed\DashedCore\Models\PersonalDataRequest;
use Dashed\DashedCore\Jobs\ProcessPersonalDataRequest;
use Dashed\DashedCore\PersonalData\PersonalDataRunner;
use Dashed\DashedCore\PersonalData\PersonalDataSubject;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource;

class ViewPersonalDataRequest extends ViewRecord
{
    protected static string $resource = PersonalDataRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('Overzicht vernieuwen'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->canEditRecord() && filled($this->record->email) && ! $this->record->isRunning())
                ->action(function () {
                    app(PersonalDataRunner::class)->previewRequest($this->record);
                    $this->record->refresh();
                    Notification::make()->title(__('Het overzicht is bijgewerkt'))->success()->send();
                }),

            Action::make('executeExport')
                ->label(__('Export maken'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => ! $this->record->isErase() && $this->canEditRecord() && $this->record->canBeExecuted())
                ->requiresConfirmation()
                ->modalHeading(__('Export maken'))
                ->modalDescription(__('Alle gevonden gegevens gaan in een ZIP-bestand dat alleen beheerders kunnen downloaden. Je krijgt een melding zodra het klaar is.'))
                ->action(fn () => $this->queue()),

            Action::make('executeErase')
                ->label(__('Gegevens verwijderen'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => $this->record->isErase() && PersonalDataRequestResource::canErase() && $this->record->canBeExecuted())
                ->requiresConfirmation()
                ->modalHeading(__('Persoonsgegevens definitief verwijderen'))
                ->modalDescription(__('Dit is niet terug te draaien. Bestellingen en facturen blijven bewaard (fiscale bewaarplicht); al het andere uit het overzicht wordt verwijderd of geanonimiseerd. Typ het e-mailadres ter bevestiging.'))
                ->schema([
                    TextInput::make('confirm_email')
                        ->label(__('E-mailadres'))
                        ->required()
                        ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                            if (PersonalDataSubject::normalize((string) $value) !== PersonalDataSubject::normalize((string) $this->record->email)) {
                                $fail(__('Het e-mailadres komt niet overeen met het verzoek.'));
                            }
                        }),
                ])
                ->action(function () {
                    abort_unless(PersonalDataRequestResource::canErase(), 403);
                    $this->queue();
                }),

            Action::make('download')
                ->label(__('Export downloaden'))
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->record->hasDownload())
                ->action(fn () => Storage::disk($this->record->file_disk)
                    ->download($this->record->file_path, 'avg-export-' . $this->record->id . '.zip')),
        ];
    }

    protected function canEditRecord(): bool
    {
        return (bool) auth()->user()?->can('edit_personal_data_request');
    }

    protected function queue(): void
    {
        $record = $this->record;
        abort_unless($record->canBeExecuted(), 422);

        $record->forceFill([
            'status' => PersonalDataRequest::STATUS_QUEUED,
            'executed_by' => auth()->id(),
            'queued_at' => now(),
            'error' => null,
        ])->save();

        ProcessPersonalDataRequest::dispatch($record->id);

        $this->record->refresh();

        Notification::make()
            ->title(__('Het verzoek wordt uitgevoerd'))
            ->body(__('Je ziet de voortgang bij de taken in de topbalk.'))
            ->success()
            ->send();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Verzoek'))
                ->schema([
                    TextEntry::make('email')
                        ->label(__('E-mailadres'))
                        ->placeholder(__('Verwijderd na uitvoering')),
                    TextEntry::make('email_hash')
                        ->label(__('Hash van het e-mailadres'))
                        ->copyable(),
                    TextEntry::make('type')
                        ->label(__('Soort'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => PersonalDataRequest::typeLabels()[$state] ?? $state),
                    TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => PersonalDataRequest::statusLabels()[$state] ?? $state)
                        ->color(fn (string $state): string => PersonalDataRequest::statusColors()[$state] ?? 'gray'),
                    TextEntry::make('requested_at')
                        ->label(__('Verzoek gedaan op'))
                        ->date('d-m-Y'),
                    TextEntry::make('verification_method')
                        ->label(__('Verificatie'))
                        ->formatStateUsing(fn (?string $state): string => PersonalDataRequest::verificationLabels()[$state] ?? (string) $state)
                        ->placeholder('-'),
                    TextEntry::make('verification_note')
                        ->label(__('Toelichting op de controle'))
                        ->placeholder('-')
                        ->columnSpanFull(),
                    TextEntry::make('creator.name')
                        ->label(__('Aangemaakt door'))
                        ->placeholder('-'),
                    TextEntry::make('created_at')
                        ->label(__('Aangemaakt op'))
                        ->dateTime('d-m-Y H:i'),
                    TextEntry::make('executor.name')
                        ->label(__('Uitgevoerd door'))
                        ->placeholder('-'),
                    TextEntry::make('completed_at')
                        ->label(__('Afgehandeld op'))
                        ->dateTime('d-m-Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('file_deleted_at')
                        ->label(__('Exportbestand opgeruimd op'))
                        ->dateTime('d-m-Y H:i')
                        ->placeholder('-')
                        ->visible(fn (PersonalDataRequest $record) => ! $record->isErase()),
                    TextEntry::make('error')
                        ->label(__('Fout'))
                        ->color('danger')
                        ->placeholder('-')
                        ->columnSpanFull()
                        ->visible(fn (PersonalDataRequest $record) => filled($record->error)),
                ])
                ->columns(2),
            Section::make(__('Logboek: wat er is gebeurd'))
                ->visible(fn (PersonalDataRequest $record) => filled($record->log))
                ->schema([
                    ViewEntry::make('log')
                        ->hiddenLabel()
                        ->view('dashed-core::filament.personal-data-findings'),
                ]),
            Section::make(__('Overzicht: wat er gevonden is en wat er gebeurt'))
                ->description(fn (PersonalDataRequest $record) => $record->previewed_at
                    ? __('Droog overzicht van :moment. Er is nog niets gewijzigd tot je het verzoek uitvoert.', ['moment' => $record->previewed_at->format('d-m-Y H:i')])
                    : null)
                ->collapsed(fn (PersonalDataRequest $record) => filled($record->log))
                ->schema([
                    ViewEntry::make('preview')
                        ->hiddenLabel()
                        ->view('dashed-core::filament.personal-data-findings'),
                ]),
        ]);
    }
}
