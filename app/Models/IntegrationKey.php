<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationKey extends Model
{
    protected $fillable = ['name', 'prefix', 'key_hash', 'scopes', 'expires_at', 'revoked_at', 'last_used_at', 'created_by'];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
