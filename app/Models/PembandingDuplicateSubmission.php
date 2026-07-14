<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PembandingDuplicateSubmission extends Model
{
    use HasUuids, Prunable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'payload',
        'image_path',
        'image_original_name',
        'image_mime_type',
        'fingerprint',
        'candidate_versions',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'candidate_versions' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleted(function (self $submission): void {
            Storage::disk('local')->delete($submission->image_path);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        Storage::disk('local')->delete($this->image_path);
    }

    /** @return array<int> */
    public function candidateIds(): array
    {
        return array_map('intval', array_keys($this->candidate_versions ?? []));
    }
}
