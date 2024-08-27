<?php

namespace Dashed\DashedCore\Livewire\Infolists\SEO;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Livewire\Component;

class SEOScoreInfoList extends Component implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    public function infoList(Infolist $infolist): Infolist
    {
        $seoScore = $this->record->seoScores->first();

        $succeededChecks = [];
        $failedChecks = [];

        if ($seoScore) {
            foreach ($seoScore->checks['successful'] ?? [] as $check) {
                $succeededChecks[] = KeyValueEntry::make($check['title'])
                    ->label($check['title'])
                    ->keyLabel('')
                    ->valueLabel('')
                    ->state([
                        'Naam' => $check['title'],
                        'Prioriteit' => $check['priority'],
                        'Beschrijving' => $check['description'],
                    ]);
            }
            foreach ($seoScore->checks['failed'] ?? [] as $check) {
                $failedChecks[] = KeyValueEntry::make($check['title'])
                    ->label($check['title'])
                    ->keyLabel('')
                    ->valueLabel('')
                    ->state([
                        'Naam' => $check['title'],
                        'Prioriteit' => $check['priority'],
                        'Beschrijving' => $check['description'],
                        'Geschatte tijd om te fixen' => $check['timeToFix'].' minuten',
                        'Gewicht in score' => $check['scoreWeight'],
                        'Reden van falen' => $check['failureReason'],
                    ]);
            }
        }

        return $infolist
            ->record($this->record)
            ->schema([
                TextEntry::make('seoScore')
                    ->label('Huidige SEO score')
                    ->visible((bool) $seoScore)
                    ->state($seoScore->score.' van de 100')
                    ->helperText('Let op: niet alles is altijd op te lossen en een 100% score is niet vereist.'),
                TextEntry::make('seoScore')
                    ->label('Huidige SEO score')
                    ->hidden((bool) $seoScore)
                    ->state('Er is nog geen SEO score bekend, sla op om te laten berekenen'),
                Section::make('Gelukte checks')
                    ->visible((bool) $seoScore)
                    ->schema($succeededChecks)
                    ->visible(count($succeededChecks)),
                Section::make('Mislukte checks')
                    ->visible((bool) $seoScore)
                    ->schema($failedChecks)
                    ->visible(count($failedChecks)),
            ]);
    }

    public function render()
    {
        return view('dashed-core::infolists.seo.seo-score-info-list');
    }
}
