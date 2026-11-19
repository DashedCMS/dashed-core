<?php

namespace Dashed\DashedCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dashed\DashedCore\Models\BackgroundTask;
use Dashed\DashedCore\Models\PersonalDataRequest;
use Dashed\DashedCore\PersonalData\PersonalDataRunner;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource;

/**
 * Voert een AVG-verzoek uit in de wachtrij en meldt zich in het taakcentrum.
 * Het label en de melding noemen alleen het nummer van het verzoek, nooit
 * het e-mailadres: de takenlijst wordt niet mee opgeruimd.
 */
class ProcessPersonalDataRequest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $requestId)
    {
    }

    public function handle(PersonalDataRunner $runner): void
    {
        $request = PersonalDataRequest::query()->find($this->requestId);

        if (! $request || ! in_array($request->status, [PersonalDataRequest::STATUS_QUEUED, PersonalDataRequest::STATUS_NEW, PersonalDataRequest::STATUS_FAILED], true)) {
            return;
        }

        $task = BackgroundTask::begin(
            'personal-data-request',
            $request->isErase()
                ? __('AVG-verzoek #:id: gegevens verwijderen', ['id' => $request->id])
                : __('AVG-verzoek #:id: export maken', ['id' => $request->id]),
            user: $request->executed_by,
        );

        $request = $runner->execute($request);

        $url = rescue(fn () => PersonalDataRequestResource::getUrl('view', ['record' => $request]), null, report: false);

        if ($request->status === PersonalDataRequest::STATUS_COMPLETED) {
            $task->finish(
                $request->isErase() ? __('De gegevens zijn verwijderd of geanonimiseerd.') : __('De export staat klaar om te downloaden.'),
                $url,
                __('Verzoek bekijken'),
            );
        } else {
            $task->fail((string) $request->error);
        }
    }

    public function failed(?\Throwable $e): void
    {
        PersonalDataRequest::query()->whereKey($this->requestId)->update([
            'status' => PersonalDataRequest::STATUS_FAILED,
            'error' => $e?->getMessage(),
        ]);
    }
}
