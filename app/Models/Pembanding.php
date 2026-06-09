<?php

namespace App\Models;

use App\Models\Traits\PembandingPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pembanding extends Model
{
    use HasFactory, LogsActivity, PembandingPresenter, SoftDeletes;

    protected $table = 'data_pembanding';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        // NEW (relations)
        'jenis_listing_id',
        'jenis_objek_id',
        'status_pemberi_informasi_id',
        'bentuk_tanah_id',
        'dokumen_tanah_id',
        'posisi_tanah_id',
        'kondisi_tanah_id',
        'topografi_id',
        'peruntukan_id',
        // Other fields (unchanged)
        'nomer_telepon_pemberi_informasi',
        'nama_pemberi_informasi',
        'luas_tanah',
        'luas_bangunan',
        'tahun_bangun',
        'lebar_depan',
        'lebar_jalan',
        'rasio_tapak',
        'harga',
        'jangka_waktu_sewa',
        'satuan_waktu_sewa',
        'tanggal_data',
        'image',
        'catatan',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'alamat_data',
        'latitude',
        'longitude',
        'created_by',
        'deleted_by_id',
        'deleted_reason',
    ];

    protected $hidden = [
        'image',
    ];

    protected $casts = [
        'luas_tanah' => 'float',
        'luas_bangunan' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'harga' => 'float',
        'jangka_waktu_sewa' => 'float',
        'tanggal_data' => 'date:Y-m-d',
    ];

    protected $with = [
        'jenisListing',
        'jenisObjek',
        'statusPemberiInformasi',
        'bentukTanah',
        'dokumenTanah',
        'posisiTanah',
        'kondisiTanah',
        'topografiRef',
        'peruntukanRef',
        'deletedBy',
    ];

    protected $appends = [
        'image_path',
        'is_sewa',
        'sewa_periode_label',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Mencatat semua field
            ->logOnlyDirty() // HANYA mencatat field yang BERUBAH saja
            ->dontSubmitEmptyLogs(); // Jangan catat jika tidak ada perubahan
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id', 'id');
    }

    public function creator()
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

    public function deleteRequests(): HasMany
    {
        return $this->hasMany(PembandingDeleteRequest::class, 'pembanding_id');
    }

    public function jenisListing(): BelongsTo
    {
        return $this->belongsTo(JenisListing::class, 'jenis_listing_id')
            ->where('is_active', true);
    }

    public function jenisObjek(): BelongsTo
    {
        return $this->belongsTo(JenisObjek::class, 'jenis_objek_id')
            ->where('is_active', true);
    }

    public function statusPemberiInformasi(): BelongsTo
    {
        return $this->belongsTo(StatusPemberiInformasi::class, 'status_pemberi_informasi_id');
    }

    public function bentukTanah(): BelongsTo
    {
        return $this->belongsTo(BentukTanah::class, 'bentuk_tanah_id');
    }

    public function dokumenTanah(): BelongsTo
    {
        return $this->belongsTo(DokumenTanah::class, 'dokumen_tanah_id');
    }

    public function posisiTanah(): BelongsTo
    {
        return $this->belongsTo(PosisiTanah::class, 'posisi_tanah_id');
    }

    public function kondisiTanah(): BelongsTo
    {
        return $this->belongsTo(KondisiTanah::class, 'kondisi_tanah_id');
    }

    /**
     * Temporary names to avoid collision with legacy string columns:
     * - topografi (string)
     * - peruntukan (string)
     */
    public function topografiRef(): BelongsTo
    {
        return $this->belongsTo(Topografi::class, 'topografi_id');
    }

    public function peruntukanRef(): BelongsTo
    {
        return $this->belongsTo(Peruntukan::class, 'peruntukan_id');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['district_id'] ?? null, function ($query, $value) {
                $query->where('district_id', $value);
            })
            ->when($filters['peruntukan'] ?? null, function ($query, $value) {
                $query->whereHas('peruntukanRef', function ($relationQuery) use ($value) {
                    $relationQuery->where('slug', $value);
                });
            })
            ->when($filters['jenis_objek'] ?? null, function ($query, $value) {
                $query->whereHas('jenisObjek', function ($relationQuery) use ($value) {
                    $relationQuery->where('slug', $value);
                });
            })
            ->when($filters['min_harga'] ?? null, function ($query, $value) {
                $query->where('harga', '>=', $value);
            })
            ->when($filters['max_harga'] ?? null, function ($query, $value) {
                $query->where('harga', '<=', $value);
            });
    }

    public function getImagePathAttribute(): ?string  // Must be PUBLIC
    {
        if (! $this->image) {
            return null;
        }

        return asset('storage/'.$this->image);
    }
}
