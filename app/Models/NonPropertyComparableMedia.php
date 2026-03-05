<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonPropertyComparableMedia extends Model
{
    use HasFactory;

    protected $table = 'np_comparable_media';

    protected $fillable = [
        'np_comparable_id',
        'media_type',
        'file_path',
        'external_url',
        'caption',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function comparable(): BelongsTo
    {
        return $this->belongsTo(NonPropertyComparable::class, 'np_comparable_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
