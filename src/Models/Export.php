<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    protected $table = 'dashed__exports';

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'parameters',
        'disk',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'file_size' => 'integer',
        'completed_at' => 'datetime',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(string $filePath, string $fileName, ?int $fileSize = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize ?? Storage::disk($this->disk)->size($filePath),
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function fileExists(): bool
    {
        if (! $this->file_path) {
            return false;
        }

        return Storage::disk($this->disk)->exists($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk($this->disk)->exists($this->file_path)) {
            Storage::disk($this->disk)->delete($this->file_path);
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'In wachtrij',
            self::STATUS_PROCESSING => 'Bezig...',
            self::STATUS_COMPLETED => 'Voltooid',
            self::STATUS_FAILED => 'Mislukt',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_QUEUED => 'gray',
            self::STATUS_PROCESSING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::deleting(function (Export $export) {
            $export->deleteFile();
        });
    }
}
