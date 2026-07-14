<?php

namespace App\Services\BulkExcelImport;

use App\Http\Requests\App\PembandingStoreRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BulkExcelImportDraftReadinessService
{
    private const FIELD_LABELS = [
        'jenis_listing_id' => 'Jenis listing', 'jenis_objek_id' => 'Jenis objek',
        'nama_pemberi_informasi' => 'Nama pemberi informasi', 'alamat_data' => 'Alamat lengkap',
        'province_id' => 'Provinsi', 'regency_id' => 'Kabupaten/Kota', 'district_id' => 'Kecamatan',
        'village_id' => 'Desa/Kelurahan', 'latitude' => 'Latitude', 'longitude' => 'Longitude',
        'luas_tanah' => 'Luas tanah', 'luas_bangunan' => 'Luas bangunan',
        'lebar_depan' => 'Lebar depan', 'lebar_jalan' => 'Lebar jalan', 'tahun_bangun' => 'Tahun bangun',
        'bentuk_tanah_id' => 'Bentuk tanah', 'posisi_tanah_id' => 'Posisi tanah',
        'kondisi_tanah_id' => 'Kondisi tanah', 'topografi_id' => 'Topografi',
        'dokumen_tanah_id' => 'Dokumen tanah', 'peruntukan_id' => 'Peruntukan',
        'harga' => 'Harga', 'jangka_waktu_sewa' => 'Jangka waktu sewa',
        'satuan_waktu_sewa' => 'Satuan waktu sewa', 'image' => 'Gambar',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     is_ready: bool,
     *     missing_fields: array<int, array{field: string, label: string, message: string}>,
     *     validation_errors: array<int, array{field: string, message: string}>
     * }
     */
    public function evaluate(array $payload, bool $hasStagingImage): array
    {
        $payload['tanggal_data'] = now()->toDateString();
        $request = PembandingStoreRequest::create('/', 'POST', $payload);
        $rules = $request->rules();
        unset($rules['image']);

        $validator = Validator::make($payload, $rules, $request->messages());
        $validator->passes();

        $missing = [];
        $invalid = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            $failedRules = array_keys($validator->failed()[$field] ?? []);
            $isMissing = collect($failedRules)->contains(
                fn (string $rule): bool => in_array($rule, ['Required', 'RequiredIf'], true),
            );

            foreach ($messages as $message) {
                if ($isMissing) {
                    $missing[] = [
                        'field' => $field,
                        'label' => self::FIELD_LABELS[$field] ?? Str::headline($field),
                        'message' => $message,
                    ];
                } else {
                    $invalid[] = ['field' => $field, 'message' => $message];
                }
            }
        }

        if (! $hasStagingImage) {
            $missing[] = [
                'field' => 'image',
                'label' => 'Gambar',
                'message' => $request->messages()['image.required'],
            ];
        }

        return [
            'is_ready' => $missing === [] && $invalid === [],
            'missing_fields' => $missing,
            'validation_errors' => $invalid,
        ];
    }
}
