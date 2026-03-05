<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonPropertyBargeSpec extends Model
{
    use HasFactory;

    protected $table = 'np_barge_specs';

    protected $fillable = [
        'np_comparable_id',
        'barge_type',
        'capacity_dwt',
        'loa_m',
        'beam_m',
        'draft_m',
        'gross_tonnage',
        'built_year',
        'shipyard',
        'hull_material',
        'class_status',
        'certificate_valid_until',
        'last_docking_date',
    ];

    protected $casts = [
        'capacity_dwt' => 'integer',
        'loa_m' => 'float',
        'beam_m' => 'float',
        'draft_m' => 'float',
        'gross_tonnage' => 'integer',
        'built_year' => 'integer',
        'certificate_valid_until' => 'date:Y-m-d',
        'last_docking_date' => 'date:Y-m-d',
    ];

    public function comparable(): BelongsTo
    {
        return $this->belongsTo(NonPropertyComparable::class, 'np_comparable_id');
    }
}
