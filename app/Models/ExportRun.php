<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExportRun extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id', 'status', 'format', 'mode', 'profile', 'scope', 'filters', 'selected_ids', 'columns',
        'snapshot_at', 'total_records', 'processed_records', 'disk', 'path', 'filename', 'checksum', 'error',
        'started_at', 'completed_at', 'failed_at', 'expires_at', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'selected_ids' => 'array',
            'columns' => 'array',
            'snapshot_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && filled($this->path)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
            'status', 'format', 'mode', 'profile', 'scope', 'total_records', 'processed_records',
            'filename', 'checksum', 'expires_at', 'downloaded_at',
        ])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
