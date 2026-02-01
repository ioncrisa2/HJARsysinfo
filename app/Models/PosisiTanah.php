<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosisiTanah extends Model
{
    protected $table = 'master_posisi_tanah';

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
        return $this->hasMany(\App\Models\Pembanding::class, 'posisi_tanah');
    }
}
