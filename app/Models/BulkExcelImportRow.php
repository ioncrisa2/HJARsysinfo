<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkExcelImportRow extends Model
{
    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_NEEDS_CONFIRMATION = 'needs_confirmation';

    public const STATUS_READY = 'ready';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_FAILED = 'failed';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FINAL_DUPLICATE = 'final_duplicate';

    public const STATUS_SOURCE_ALREADY_IMPORTED = 'source_already_imported';

    protected $fillable = [
        'batch_id',
        'source_row_number',
        'source_fingerprint',
        'imported_source_fingerprint',
        'status',
        'is_selected',
        'raw_payload',
        'mapped_payload',
        'missing_fields',
        'warnings',
        'staging_image_disk',
        'staging_image_path',
        'staging_image_original_name',
        'staging_image_mime',
        'staging_image_size',
        'duplicate_of_row_id',
        'pembanding_id',
        'conflicting_pembanding_id',
        'attempts',
        'last_error',
        'failure_code',
    ];

    protected $hidden = [
        'source_fingerprint',
        'imported_source_fingerprint',
        'raw_payload',
        'staging_image_disk',
        'staging_image_path',
        'staging_image_original_name',
        'staging_image_mime',
        'staging_image_size',
    ];

    protected function casts(): array
    {
        return [
            'is_selected' => 'boolean',
            'raw_payload' => 'array',
            'mapped_payload' => 'array',
            'missing_fields' => 'array',
            'warnings' => 'array',
            'staging_image_size' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BulkExcelImportBatch::class, 'batch_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_row_id');
    }

    public function pembanding(): BelongsTo
    {
        return $this->belongsTo(Pembanding::class);
    }

    public function conflictingPembanding(): BelongsTo
    {
        return $this->belongsTo(Pembanding::class, 'conflicting_pembanding_id');
    }
}
