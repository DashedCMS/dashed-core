<?php

declare(strict_types=1);

namespace Dashed\DashedCore\PersonalData;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Dashed\DashedCore\Models\PersonalDataRequest;

/**
 * Loopt alle providers af voor een verzoek: het droge overzicht, de export
 * of de verwijdering. Een provider die faalt stopt de rest niet; zijn fout
 * komt in het logboek en het verzoek eindigt dan als mislukt, zodat het
 * opnieuw kan.
 */
class PersonalDataRunner
{
    public function __construct(protected PersonalDataRegistry $registry)
    {
    }

    /**
     * @return array<int, array{provider: string, label: string, findings: array<int, array<string, mixed>>, error: ?string}>
     */
    public function preview(PersonalDataSubject $subject): array
    {
        return $this->each(fn (PersonalDataProvider $provider) => $provider->find($subject));
    }

    public function previewRequest(PersonalDataRequest $request): PersonalDataRequest
    {
        $request->forceFill([
            'preview' => $this->preview($request->subject()),
            'previewed_at' => now(),
        ])->save();

        return $request;
    }

    public function execute(PersonalDataRequest $request): PersonalDataRequest
    {
        $request->forceFill(['status' => PersonalDataRequest::STATUS_PROCESSING, 'started_at' => now(), 'error' => null])->save();

        try {
            $request->isErase() ? $this->erase($request) : $this->export($request);
        } catch (Throwable $e) {
            report($e);
            $request->forceFill([
                'status' => PersonalDataRequest::STATUS_FAILED,
                'error' => $e->getMessage(),
            ])->save();
        }

        return $request->refresh();
    }

    protected function export(PersonalDataRequest $request): void
    {
        $subject = $request->subject();
        $export = new PersonalDataExport();

        $log = $this->each(function (PersonalDataProvider $provider) use ($subject, $export) {
            $provider->export($subject, $export->forProvider($provider->key()));

            return $provider->find($subject);
        });

        $tmp = $export->build([
            'request_id' => $request->id,
            'email' => $subject->email,
        ]);

        try {
            $disk = (string) config('dashed-core.personal_data.disk', 'local');
            $path = 'personal-data-exports/' . $request->id . '/' . bin2hex(random_bytes(16)) . '.zip';
            $stream = fopen($tmp, 'r');
            Storage::disk($disk)->put($path, $stream, ['visibility' => 'private']);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($request->file_path) {
                rescue(fn () => Storage::disk($request->file_disk)->delete($request->file_path), report: false);
            }

            $request->forceFill([
                'file_disk' => $disk,
                'file_path' => $path,
                'file_size' => filesize($tmp) ?: null,
                'file_deleted_at' => null,
            ]);
        } finally {
            @unlink($tmp);
        }

        $this->finish($request, $log);
    }

    protected function erase(PersonalDataRequest $request): void
    {
        $subject = $request->subject();

        $log = $this->each(fn (PersonalDataProvider $provider) => DB::transaction(fn () => $provider->erase($subject)));

        $this->finish($request, $log);

        // Pas na een volledig geslaagde verwijdering het adres weghalen: een
        // mislukte poging moet met hetzelfde adres opnieuw kunnen.
        if ($request->status === PersonalDataRequest::STATUS_COMPLETED) {
            $request->forceFill(['email' => null])->save();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $log
     */
    protected function finish(PersonalDataRequest $request, array $log): void
    {
        $failed = collect($log)->contains(fn (array $entry) => filled($entry['error']));

        $request->forceFill([
            'log' => $log,
            'status' => $failed ? PersonalDataRequest::STATUS_FAILED : PersonalDataRequest::STATUS_COMPLETED,
            'error' => $failed ? __('Niet elke bron is gelukt, zie het logboek.') : null,
            'completed_at' => now(),
        ])->save();
    }

    /**
     * @param  callable(PersonalDataProvider): array<int, PersonalDataFinding>  $callback
     * @return array<int, array{provider: string, label: string, findings: array<int, array<string, mixed>>, error: ?string}>
     */
    protected function each(callable $callback): array
    {
        $result = [];

        foreach ($this->registry->all() as $provider) {
            $entry = ['provider' => $provider->key(), 'label' => $provider->label(), 'findings' => [], 'error' => null];

            try {
                $entry['findings'] = array_map(fn (PersonalDataFinding $finding) => $finding->toArray(), $callback($provider));
            } catch (Throwable $e) {
                report($e);
                $entry['error'] = $e->getMessage();
            }

            $result[] = $entry;
        }

        return $result;
    }
}
