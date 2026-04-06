<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Jobs\GenerateArticleJob;
use Dashed\DashedCore\Models\ArticleDraft;
use Dashed\DashedCore\Filament\Resources\ArticleDraftResource;

class GenerateArticleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generate-article';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Artikel schrijven')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->visible(fn () => ClaudeHelper::isConnected())
            ->schema([
                TextInput::make('keyword')
                    ->label('Zoekwoord / onderwerp')
                    ->placeholder('Bijv: duurzame tuinmeubelen')
                    ->required()
                    ->default(fn () => $this->tryGetRecordName()),
                Select::make('locale')
                    ->label('Taal')
                    ->options(Locales::getLocalesArray())
                    ->default(Locales::getFirstLocale()['id'] ?? 'nl')
                    ->required(),
                Textarea::make('instruction')
                    ->label('Extra instructie (optioneel)')
                    ->placeholder('Bijv: schrijf vanuit het oogpunt van een expert, focus op beginners')
                    ->rows(2),
            ])
            ->action(function (array $data): void {
                $draft = ArticleDraft::create([
                    'keyword' => $data['keyword'],
                    'locale' => $data['locale'],
                    'instruction' => $data['instruction'] ?? null,
                    'status' => 'pending',
                    'subject_type' => $this->record ? get_class($this->record) : null,
                    'subject_id' => $this->record?->getKey(),
                ]);

                GenerateArticleJob::dispatch($draft);

                Notification::make()
                    ->title('Artikel wordt geschreven')
                    ->body('Je wordt doorgestuurd naar de voortgangspagina.')
                    ->success()
                    ->send();

                redirect(ArticleDraftResource::getUrl('view', ['record' => $draft]));
            });
    }

    private function tryGetRecordName(): string
    {
        if (! $this->record) {
            return '';
        }
        try {
            $locale = app()->getLocale();
            return method_exists($this->record, 'getTranslation')
                ? ($this->record->getTranslation('name', $locale) ?: '')
                : ($this->record->name ?? '');
        } catch (\Throwable) {
            return '';
        }
    }
}
