<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusPemberiInformasi extends Model
{
    protected $table = 'master_status_pemberi_informasi';

    protected $fillable = ['slug', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public static function options(bool $onlyActive = true): array
    {
        $q = static::query()->orderBy('sort_order')->orderBy('name');
        if ($onlyActive) $q->where('is_active', true);

        return $q->pluck('name', 'slug')->all();
    }

    public function pembandings(): HasMany
    {
        return $this->hasMany(\App\Models\Pembanding::class, 'status_pemberi_informasi_id');
    }
}
