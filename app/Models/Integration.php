<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    protected $fillable = ['name', 'is_active', 'requests_per_minute', 'created_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'requests_per_minute' => 'integer'];
    }

    public function keys(): HasMany
    {
        return $this->hasMany(IntegrationKey::class);
    }
}
