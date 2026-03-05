<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonPropertyVehicleSpec extends Model
{
    use HasFactory;

    protected $table = 'np_vehicle_specs';

    protected $fillable = [
        'np_comparable_id',
        'vehicle_type',
        'axle_configuration',
        'odometer_km',
        'transmission',
        'fuel_type',
        'engine_cc',
        'payload_kg',
        'body_type',
        'drive_type',
    ];

    protected $casts = [
        'odometer_km' => 'integer',
        'engine_cc' => 'integer',
        'payload_kg' => 'integer',
    ];

    public function comparable(): BelongsTo
    {
        return $this->belongsTo(NonPropertyComparable::class, 'np_comparable_id');
    }
}
