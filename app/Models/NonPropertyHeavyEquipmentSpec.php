<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonPropertyHeavyEquipmentSpec extends Model
{
    use HasFactory;

    protected $table = 'np_heavy_equipment_specs';

    protected $fillable = [
        'np_comparable_id',
        'equipment_type',
        'hour_meter',
        'operating_weight_kg',
        'bucket_capacity_m3',
        'engine_power_hp',
        'undercarriage_type',
        'undercarriage_condition',
        'attachment',
        'service_history_note',
    ];

    protected $casts = [
        'hour_meter' => 'integer',
        'operating_weight_kg' => 'integer',
        'bucket_capacity_m3' => 'float',
        'engine_power_hp' => 'integer',
    ];

    public function comparable(): BelongsTo
    {
        return $this->belongsTo(NonPropertyComparable::class, 'np_comparable_id');
    }
}
