<?php

namespace App\Models;


use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\PembandingPresenter;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembanding extends Model
{
    use HasFactory, PembandingPresenter, SoftDeletes, LogsActivity;

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

    protected $casts = [
        'luas_tanah'                => 'float',
        'luas_bangunan'             => 'float',
        'latitude'                  => 'float',
        'longitude'                 => 'float',
        'harga'                     => 'float'
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
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Mencatat semua field
            ->logOnlyDirty() // HANYA mencatat field yang BERUBAH saja
            ->dontSubmitEmptyLogs(); // Jangan catat jika tidak ada perubahan
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['district_id'] ?? null, function ($query, $value) {
                $query->where('district_id', $value);
            })
            ->when($filters['peruntukan'] ?? null, function ($query, $value) {
                $query->where('peruntukan', $value);
            })
            ->when($filters['jenis_objek'] ?? null, function ($query, $value) {
                $query->where('jenis_objek', $value);
            })
            ->when($filters['min_harga'] ?? null, function ($query, $value) {
                $query->where('harga', '>=', $value);
            })
            ->when($filters['max_harga'] ?? null, function ($query, $value) {
                $query->where('harga', '<=', $value);
            });
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

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by_id');
    }

    public function jenisListing(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JenisListing::class, 'jenis_listing_id');
    }

    public function jenisObjek(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JenisObjek::class, 'jenis_objek_id');
    }

    public function statusPemberiInformasi(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StatusPemberiInformasi::class, 'status_pemberi_informasi_id');
    }

    public function bentukTanah(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BentukTanah::class, 'bentuk_tanah_id');
    }

    public function dokumenTanah(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DokumenTanah::class, 'dokumen_tanah_id');
    }

    public function posisiTanah(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PosisiTanah::class, 'posisi_tanah_id');
    }

    public function kondisiTanah(): BelongsTo
    {
        return $this->belongsTo(\App\Models\KondisiTanah::class, 'kondisi_tanah_id');
    }

    /**
     * Temporary names to avoid collision with legacy string columns:
     * - topografi (string)
     * - peruntukan (string)
     */
    public function topografiRef(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Topografi::class, 'topografi_id');
    }

    public function peruntukanRef(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Peruntukan::class, 'peruntukan_id');
    }
}
