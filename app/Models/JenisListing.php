<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisListing extends Model
{
    protected $table = 'master_jenis_listing';

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
        'is_active',
        'badge_color',
        'marker_icon_url'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    /**
     * Convenience helper for Select options: [slug => name]
     */
    public static function options(bool $onlyActive = true): array
    {
        $q = static::query()->orderBy('sort_order')->orderBy('name');
        if ($onlyActive) $q->where('is_active', true);

        return $q
            ->pluck('name', 'slug')
            ->all();
    }

    public function pembandings(): HasMany
    {
        return $this->hasMany(\App\Models\Pembanding::class, 'jenis_listing_id');
    }
}
