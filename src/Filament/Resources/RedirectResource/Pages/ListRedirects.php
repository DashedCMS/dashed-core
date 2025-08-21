<?php

namespace Dashed\DashedCore\Filament\Resources\RedirectResource\Pages;

use Dashed\DashedCore\Imports\ArrayImport;
use Dashed\DashedCore\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('bulkUpload')
                ->label('Bulk upload')
                ->form([
                    FileUpload::make('file')
                        ->label('Upload CSV file')
                        ->required()
                        ->disk('dashed')
                        ->directory('redirects/bulk-uploads')
                        ->acceptedFileTypes(['text/csv', 'application/csv'])
                        ->helperText('Upload een CSV bestand met "from,to,sort" kolommen. De eerste kolom wordt niet ingeladen. sort mag zijn: 301 of 302.'),
                    DatePicker::make('date')
                        ->label('Datum')
                        ->required()
                        ->minDate(now())
                        ->helperText('Tot wanneer moeten de redirects actief zijn?')
                        ->default(now()->addMonths(3))
                ])
                ->action(function (array $data) {
                    $redirects = Excel::toArray(new ArrayImport(), $data['file'], 'dashed')[0] ?? [];
                    unset($redirects[0]);
                    $totalEntries = 0;

                    foreach ($redirects as $redirect) {
                        $sort = $redirect[2] ?? '301';
                        if(!in_array($sort, ['301', '302'])) {
                            $sort = 301;
                        }
                        $newRedirect = new Redirect();
                        $newRedirect->from = $redirect[0];
                        $newRedirect->to = $redirect[1];
                        $newRedirect->sort = $sort;
                        $newRedirect->delete_redirect_after = $data['date'];
                        $newRedirect->save();
                        $totalEntries++;
                    }

                    Storage::disk('dashed')->delete($data['file']);

                    Notification::make()
                        ->body("De redirects (' . $totalEntries . ') zijn geimporteerd.")
                        ->success()
                        ->send();
                })
        ];
    }
}
