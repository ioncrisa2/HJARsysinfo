<?php

namespace App\Models;

use App\Enums\Topografi;
use App\Enums\JenisObjek;
use App\Enums\Peruntukan;
use App\Enums\BentukTanah;
use App\Enums\PosisiTanah;
use App\Enums\DokumenTanah;
use App\Enums\JenisListing;
use App\Enums\KondisiTanah;
use Spatie\Activitylog\LogOptions;
use App\Enums\StatusPemberiInformasi;
use App\Models\Traits\PembandingFilterTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\PembandingPresenter;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembanding extends Model
{
    use HasFactory, PembandingPresenter, SoftDeletes, LogsActivity, PembandingFilterTrait;

    protected $table = 'data_pembanding';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'jenis_listing',
        'jenis_objek',
        'nomer_telepon_pemberi_informasi',
        'nama_pemberi_informasi',
        'status_pemberi_informasi',
        'luas_tanah',
        'luas_bangunan',
        'tahun_bangun',
        'bentuk_tanah',
        'dokumen_tanah',
        'posisi_tanah',
        'kondisi_tanah',
        'topografi',
        'lebar_depan',
        'lebar_jalan',
        'peruntukan',
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
    ];

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

    protected $casts = [
        'jenis_listing'             => JenisListing::class,
        'jenis_objek'               => JenisObjek::class,
        'bentuk_tanah'              => BentukTanah::class,
        'dokumen_tanah'             => DokumenTanah::class,
        'kondisi_tanah'             => KondisiTanah::class,
        'peruntukan'                => Peruntukan::class,
        'posisi_tanah'              => PosisiTanah::class,
        'status_pemberi_informasi'  => StatusPemberiInformasi::class,
        'topografi'                 => Topografi::class,
        'luas_tanah'                => 'float',
        'luas_bangunan'             => 'float',
        'latitude'                  => 'float',
        'longitude'                 => 'float',
        'harga'                     => 'float'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Mencatat semua field
            ->logOnlyDirty() // HANYA mencatat field yang BERUBAH saja
            ->dontSubmitEmptyLogs(); // Jangan catat jika tidak ada perubahan
    }
}
