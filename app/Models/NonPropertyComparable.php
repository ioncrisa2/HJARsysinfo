<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NonPropertyComparable extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'np_comparables';

    protected $fillable = [
        'comparable_code',
        'asset_category',
        'asset_subtype',
        'brand',
        'model',
        'variant',
        'manufacture_year',
        'serial_number',
        'registration_number',
        'listing_type',
        'source_platform',
        'source_name',
        'source_phone',
        'source_url',
        'location_country',
        'location_city',
        'location_address',
        'latitude',
        'longitude',
        'currency',
        'asking_price',
        'transaction_price',
        'assumed_discount_percent',
        'data_date',
        'asset_condition',
        'operational_status',
        'legal_document_status',
        'verification_status',
        'confidence_score',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by_id',
        'deleted_reason',
    ];

    protected $casts = [
        'manufacture_year' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'asking_price' => 'float',
        'transaction_price' => 'float',
        'assumed_discount_percent' => 'float',
        'confidence_score' => 'integer',
        'data_date' => 'date:Y-m-d',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('non_property_comparable')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs();
    }

    public function media(): HasMany
    {
        return $this->hasMany(NonPropertyComparableMedia::class, 'np_comparable_id')
            ->orderBy('sort_order');
    }

    public function vehicleSpec(): HasOne
    {
        return $this->hasOne(NonPropertyVehicleSpec::class, 'np_comparable_id');
    }

    public function heavyEquipmentSpec(): HasOne
    {
        return $this->hasOne(NonPropertyHeavyEquipmentSpec::class, 'np_comparable_id');
    }

    public function bargeSpec(): HasOne
    {
        return $this->hasOne(NonPropertyBargeSpec::class, 'np_comparable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
