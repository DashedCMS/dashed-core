<?php

namespace Dashed\DashedCore\Filament\Resources\ReportResource\Pages;

use Throwable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Dashed\DashedCore\Reports\ReportResult;
use Dashed\DashedCore\Reports\ReportExporter;
use Dashed\DashedCore\Mail\ScheduledReportMail;
use Dashed\DashedCore\Filament\Resources\ReportResource;

/**
 * Het resultaat van een rapport: tabel met totaalregel en een grafiek van
 * de eerste meetwaarde. De grafiek is inline SVG zodat er geen extra
 * JavaScript of Tailwind-build in een klantproject voor nodig is.
 */
class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected string $view = 'dashed-core::reports.view';

    public ?string $error = null;

    public function getResult(): ?ReportResult
    {
        try {
            return $this->record->run();
        } catch (Throwable $e) {
            report($e);
            $this->error = __('Dit rapport kon niet worden uitgerekend. Controleer de bron en de keuzes.');

            return null;
        }
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        foreach (ReportExporter::formats() as $format => $label) {
            $actions[] = Action::make('export_'.$format)
                ->label(__('Download :formaat', ['formaat' => $label]))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () use ($format) {
                    $contents = ReportExporter::contents($this->record->run(), $format);

                    return response()->streamDownload(
                        fn () => print($contents),
                        ReportExporter::filename($this->record->name, $format),
                    );
                });
        }

        $actions[] = Action::make('send_now')
            ->label(__('Nu mailen'))
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->visible(fn () => $this->record->validRecipients() !== [])
            ->requiresConfirmation()
            ->modalDescription(fn () => __('Het rapport gaat nu naar: :ontvangers', ['ontvangers' => implode(', ', $this->record->validRecipients())]))
            ->action(function () {
                Mail::to($this->record->validRecipients())->send(new ScheduledReportMail($this->record, $this->record->run()));
                Notification::make()->title(__('Het rapport is verstuurd'))->success()->send();
            });

        $actions[] = EditAction::make();

        return $actions;
    }
}
