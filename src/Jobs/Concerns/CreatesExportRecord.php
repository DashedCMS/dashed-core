<?php

namespace Dashed\DashedCore\Jobs\Concerns;

use Throwable;
use Dashed\DashedCore\Models\Export;

trait CreatesExportRecord
{
    public ?int $exportId = null;

    protected function createExportRecord(string $type, string $label, array $parameters, ?int $userId = null, string $disk = 'dashed'): Export
    {
        $export = Export::create([
            'user_id' => $userId,
            'type' => $type,
            'label' => $label,
            'parameters' => $parameters,
            'disk' => $disk,
            'status' => Export::STATUS_QUEUED,
        ]);

        $this->exportId = $export->id;

        return $export;
    }

    protected function getExportRecord(): ?Export
    {
        return $this->exportId ? Export::find($this->exportId) : null;
    }

    protected function markExportAsProcessing(): void
    {
        $this->getExportRecord()?->markAsProcessing();
    }

    protected function markExportAsCompleted(string $filePath, string $fileName, ?int $fileSize = null): void
    {
        $this->getExportRecord()?->markAsCompleted($filePath, $fileName, $fileSize);
    }

    protected function markExportAsFailed(string|Throwable $error): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;
        $this->getExportRecord()?->markAsFailed($message);
    }
}
