<?php

namespace Dashed\DashedCore\Filament\Resources\ArticleDraftResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Jobs\GenerateArticleJob;
use Dashed\DashedCore\Models\ArticleDraft;
use Dashed\DashedCore\Filament\Resources\ArticleDraftResource;

class CreateArticleDraft extends CreateRecord
{
    protected static string $resource = ArticleDraftResource::class;

    protected static ?string $title = 'Nieuw artikel schrijven';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('keyword')
                ->label('Zoekwoord / onderwerp')
                ->placeholder('Bijv: duurzame tuinmeubelen, wat is een hypotheek, enz.')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('locale')
                ->label('Taal')
                ->options(Locales::getLocalesArray())
                ->default(Locales::getFirstLocale()['id'] ?? 'nl')
                ->required(),

            Textarea::make('instruction')
                ->label('Extra instructie (optioneel)')
                ->placeholder('Bijv: schrijf vanuit het oogpunt van een expert, focus op duurzaamheid, gebruik een informele toon')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    protected function handleRecordCreation(array $data): ArticleDraft
    {
        $draft = ArticleDraft::create([
            'keyword' => $data['keyword'],
            'locale' => $data['locale'],
            'instruction' => $data['instruction'] ?? null,
            'status' => 'pending',
        ]);

        GenerateArticleJob::dispatch($draft);

        return $draft;
    }

    protected function getRedirectUrl(): string
    {
        return ArticleDraftResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Artikel wordt gegenereerd';
    }
}
