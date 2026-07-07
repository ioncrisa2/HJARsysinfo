<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P2pkImportBatch extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'owner_id',
        'original_filename',
        'source_disk',
        'source_path',
        'file_checksum',
        'sheet_name',
        'status',
        'total_rows',
        'selected_rows',
        'ready_rows',
        'imported_rows',
        'failed_rows',
        'last_activity_at',
        'finalization_date',
        'initiated_by',
        'finalized_at',
    ];

    protected $hidden = ['source_disk', 'source_path', 'file_checksum'];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'finalization_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(P2pkImportRow::class, 'batch_id')->orderBy('source_row_number');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function allowsDraftChanges(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PARTIAL, self::STATUS_FAILED], true);
    }
}
