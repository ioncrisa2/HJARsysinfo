<?php

use App\Http\Requests\App\PembandingStoreRequest;
use App\Models\BentukTanah;
use App\Models\District;
use App\Models\DokumenTanah;
use App\Models\JenisListing;
use App\Models\JenisObjek;
use App\Models\KondisiTanah;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StatusPemberiInformasi;
use App\Models\Topografi;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makePembandingRentPayload(array $overrides = []): array
{
    $province = Province::query()->firstOrCreate(['id' => '31'], ['name' => 'DKI Jakarta']);
    $regency = Regency::query()->firstOrCreate(['id' => '3171'], ['province_id' => $province->id, 'name' => 'Jakarta Selatan']);
    $district = District::query()->firstOrCreate(['id' => '317101'], ['regency_id' => $regency->id, 'name' => 'Kebayoran Baru']);
    $village = Village::query()->firstOrCreate(['id' => '3171011001'], ['district_id' => $district->id, 'name' => 'Melawai']);

    $listing = JenisListing::query()->firstOrCreate(['slug' => 'sewa'], ['name' => 'Sewa']);

    return array_merge([
        'jenis_listing_id' => $listing->id,
        'jenis_objek_id' => JenisObjek::query()->firstOrCreate(['slug' => 'ruko'], ['name' => 'Ruko'])->id,
        'nama_pemberi_informasi' => 'Sumber Sewa',
        'nomer_telepon_pemberi_informasi' => '08123456789',
        'status_pemberi_informasi_id' => StatusPemberiInformasi::query()->firstOrCreate(['slug' => 'agen'], ['name' => 'Agen'])->id,
        'tanggal_data' => '2026-05-29',
        'alamat_data' => 'Jl. Sewa No. 1',
        'province_id' => $province->id,
        'regency_id' => $regency->id,
        'district_id' => $district->id,
        'village_id' => $village->id,
        'latitude' => -6.244,
        'longitude' => 106.799,
        'image' => UploadedFile::fake()->image('sewa.jpg'),
        'luas_tanah' => 120,
        'luas_bangunan' => 80,
        'lebar_depan' => 8,
        'lebar_jalan' => 6,
        'tahun_bangun' => 2020,
        'rasio_tapak' => '0.8',
        'bentuk_tanah_id' => BentukTanah::query()->firstOrCreate(['slug' => 'persegi'], ['name' => 'Persegi'])->id,
        'posisi_tanah_id' => PosisiTanah::query()->firstOrCreate(['slug' => 'hook'], ['name' => 'Hook'])->id,
        'kondisi_tanah_id' => KondisiTanah::query()->firstOrCreate(['slug' => 'matang'], ['name' => 'Matang'])->id,
        'topografi_id' => Topografi::query()->firstOrCreate(['slug' => 'datar'], ['name' => 'Datar'])->id,
        'dokumen_tanah_id' => DokumenTanah::query()->firstOrCreate(['slug' => 'shm'], ['name' => 'SHM'])->id,
        'peruntukan_id' => Peruntukan::query()->firstOrCreate(['slug' => 'ruko'], ['name' => 'Ruko'])->id,
        'harga' => 15000000,
        'jangka_waktu_sewa' => 3,
        'satuan_waktu_sewa' => 'Bulan',
    ], $overrides);
}

function validatePembandingRentPayload(array $payload): array
{
    $request = PembandingStoreRequest::create('/home/pembanding', 'POST', $payload, [], [
        'image' => $payload['image'],
    ]);

    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->validateResolved();

    return $request->validated();
}

it('requires rent period when listing is sewa', function () {
    validatePembandingRentPayload(makePembandingRentPayload([
        'jangka_waktu_sewa' => null,
        'satuan_waktu_sewa' => null,
    ]));
})->throws(ValidationException::class);

it('accepts monthly and yearly rent periods only', function () {
    $monthly = validatePembandingRentPayload(makePembandingRentPayload([
        'jangka_waktu_sewa' => 6,
        'satuan_waktu_sewa' => 'Bulan',
    ]));

    expect($monthly['jangka_waktu_sewa'])->toBe(6)
        ->and($monthly['satuan_waktu_sewa'])->toBe('Bulan');

    validatePembandingRentPayload(makePembandingRentPayload([
        'satuan_waktu_sewa' => 'Hari',
    ]));
})->throws(ValidationException::class);

it('clears rent period fields when listing is not sewa', function () {
    $transaksi = JenisListing::query()->firstOrCreate(['slug' => 'transaksi'], ['name' => 'Transaksi']);

    $validated = validatePembandingRentPayload(makePembandingRentPayload([
        'jenis_listing_id' => $transaksi->id,
        'jangka_waktu_sewa' => 12,
        'satuan_waktu_sewa' => 'Bulan',
    ]));

    expect($validated['jangka_waktu_sewa'])->toBeNull()
        ->and($validated['satuan_waktu_sewa'])->toBeNull();
});
