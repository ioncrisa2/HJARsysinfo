<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KondisiTanah extends Model
{
    protected $table = 'master_kondisi_tanah';

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
        return $this->hasMany(\App\Models\Pembanding::class, 'kondisi_tanah_id');
    }
}
