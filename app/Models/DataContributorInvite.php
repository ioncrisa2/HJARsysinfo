<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataContributorInvite extends Model
{
    use HasFactory;

    public const STATUS_UNUSED = 'unused';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'token_hash',
        'created_by',
        'expires_at',
        'used_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrationRequest(): HasOne
    {
        return $this->hasOne(DataContributorRegistrationRequest::class, 'invite_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_UNUSED && ! $this->used_at && ! $this->isExpired();
    }

    public function markExpiredIfNeeded(): bool
    {
        if ($this->status === self::STATUS_UNUSED && $this->isExpired()) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->save();

            return true;
        }

        return false;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
