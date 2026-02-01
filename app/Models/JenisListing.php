<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisListing extends Model
{
     protected $table = 'master_jenis_listings';

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
        'badge_color',
        'marker_icon_url'
    ];

    /**
     * Convenience helper for Select options: [slug => name]
     */
    public static function options(): array
    {
        return static::query()
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    public function pembandings(): HasMany
    {
        return $this->hasMany(\App\Models\Pembanding::class, 'jenis_listing_id');
    }
}
