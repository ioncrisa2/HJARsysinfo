<?php

namespace App\Http\Requests\App;

use App\Models\JenisListing;
use App\Models\JenisObjek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PembandingStoreRequest extends FormRequest
{
    private const RENT_UNITS = ['Bulan', 'Tahun'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tahun_bangun')) {
            $year = preg_replace('/\D/', '', (string) $this->input('tahun_bangun'));
            $this->merge(['tahun_bangun' => $year === '' ? null : (int) $year]);
        }

        if ($this->has('nomer_telepon_pemberi_informasi')) {
            $this->merge([
                'nomer_telepon_pemberi_informasi' => $this->normalizePhoneNumberId($this->input('nomer_telepon_pemberi_informasi')),
            ]);
        }

        if ($this->isObjectWithoutBuildingFields()) {
            $this->merge([
                'luas_bangunan' => null,
                'tahun_bangun' => null,
            ]);
        }

        if ($this->isSewaListing()) {
            return;
        }

        $this->merge([
            'jangka_waktu_sewa' => null,
            'satuan_waktu_sewa' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_listing_id' => ['required', 'integer', 'exists:master_jenis_listing,id'],
            'jenis_objek_id' => ['required', 'integer', 'exists:master_jenis_objek,id'],
            'nama_pemberi_informasi' => ['required', 'string', 'max:255'],
            'nomer_telepon_pemberi_informasi' => ['nullable', 'string', 'max:255'],
            'status_pemberi_informasi_id' => ['nullable', 'integer', 'exists:master_status_pemberi_informasi,id'],
            'tanggal_data' => ['required', 'date_format:Y-m-d'],
            'alamat_data' => ['required', 'string', 'max:500'],
            'province_id' => ['required', 'string', 'exists:provinces,id'],
            'regency_id' => ['required', 'string', 'exists:regencies,id'],
            'district_id' => ['required', 'string', 'exists:districts,id'],
            'village_id' => ['required', 'string', 'exists:villages,id'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'image' => ['required', 'image', 'max:15360'],
            'luas_tanah' => ['required', 'numeric'],
            'luas_bangunan' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->requiresBuildingFields()),
                'numeric',
            ],
            'lebar_depan' => ['required', 'numeric'],
            'lebar_jalan' => ['required', 'numeric'],
            'tahun_bangun' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->requiresBuildingFields()),
                'integer',
                'digits:4',
                'min:1900',
                'max:'.date('Y'),
            ],
            'rasio_tapak' => ['nullable', 'string', 'max:255'],
            'bentuk_tanah_id' => ['required', 'integer', 'exists:master_bentuk_tanah,id'],
            'posisi_tanah_id' => ['required', 'integer', 'exists:master_posisi_tanah,id'],
            'kondisi_tanah_id' => ['required', 'integer', 'exists:master_kondisi_tanah,id'],
            'topografi_id' => ['required', 'integer', 'exists:master_topografi,id'],
            'dokumen_tanah_id' => ['required', 'integer', 'exists:master_dokumen_tanah,id'],
            'peruntukan_id' => ['required', 'integer', 'exists:master_peruntukan,id'],
            'harga' => ['required', 'numeric', 'min:0'],
            'jangka_waktu_sewa' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->isSewaListing()),
                'numeric',
                'min:1',
            ],
            'satuan_waktu_sewa' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->isSewaListing()),
                'string',
                Rule::in(self::RENT_UNITS),
            ],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_listing_id.required' => 'Jenis listing wajib dipilih.',
            'jenis_listing_id.exists' => 'Jenis listing tidak valid.',
            'jenis_objek_id.required' => 'Jenis objek wajib dipilih.',
            'jenis_objek_id.exists' => 'Jenis objek tidak valid.',
            'nama_pemberi_informasi.required' => 'Nama pemberi informasi wajib diisi.',
            'tanggal_data.required' => 'Tanggal data wajib diisi.',
            'tanggal_data.date_format' => 'Tanggal data harus berformat YYYY-MM-DD.',
            'alamat_data.required' => 'Alamat wajib diisi.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'province_id.exists' => 'Provinsi tidak valid.',
            'regency_id.required' => 'Kabupaten/Kota wajib dipilih.',
            'regency_id.exists' => 'Kabupaten/Kota tidak valid.',
            'district_id.required' => 'Kecamatan wajib dipilih.',
            'district_id.exists' => 'Kecamatan tidak valid.',
            'village_id.required' => 'Desa/Kelurahan wajib dipilih.',
            'village_id.exists' => 'Desa/Kelurahan tidak valid.',
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'image.required' => 'Foto properti wajib diunggah.',
            'image.image' => 'File foto harus berupa gambar.',
            'image.max' => 'Ukuran foto maksimal 15MB.',
            'luas_tanah.required' => 'Luas tanah wajib diisi.',
            'luas_tanah.numeric' => 'Luas tanah harus berupa angka.',
            'luas_bangunan.required' => 'Luas bangunan wajib diisi kecuali untuk objek Tanah, Sawah, atau Tanah Kebun.',
            'luas_bangunan.numeric' => 'Luas bangunan harus berupa angka.',
            'lebar_depan.required' => 'Lebar depan wajib diisi.',
            'lebar_depan.numeric' => 'Lebar depan harus berupa angka.',
            'lebar_jalan.required' => 'Lebar jalan wajib diisi.',
            'lebar_jalan.numeric' => 'Lebar jalan harus berupa angka.',
            'tahun_bangun.required' => 'Tahun bangun wajib diisi kecuali untuk objek Tanah, Sawah, atau Tanah Kebun.',
            'tahun_bangun.integer' => 'Tahun bangun harus berupa angka.',
            'tahun_bangun.digits' => 'Tahun bangun harus terdiri dari 4 digit.',
            'tahun_bangun.min' => 'Tahun bangun minimal 1900.',
            'tahun_bangun.max' => 'Tahun bangun tidak boleh melebihi tahun berjalan.',
            'bentuk_tanah_id.required' => 'Bentuk tanah wajib dipilih.',
            'bentuk_tanah_id.exists' => 'Bentuk tanah tidak valid.',
            'posisi_tanah_id.required' => 'Posisi tanah wajib dipilih.',
            'posisi_tanah_id.exists' => 'Posisi tanah tidak valid.',
            'kondisi_tanah_id.required' => 'Kondisi tanah wajib dipilih.',
            'kondisi_tanah_id.exists' => 'Kondisi tanah tidak valid.',
            'topografi_id.required' => 'Topografi wajib dipilih.',
            'topografi_id.exists' => 'Topografi tidak valid.',
            'dokumen_tanah_id.required' => 'Dokumen tanah wajib dipilih.',
            'dokumen_tanah_id.exists' => 'Dokumen tanah tidak valid.',
            'peruntukan_id.required' => 'Peruntukan wajib dipilih.',
            'peruntukan_id.exists' => 'Peruntukan tidak valid.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga minimal 0.',
            'jangka_waktu_sewa.required' => 'Jangka waktu sewa wajib diisi untuk properti sewa.',
            'jangka_waktu_sewa.numeric' => 'Jangka waktu sewa harus berupa angka.',
            'jangka_waktu_sewa.min' => 'Jangka waktu sewa minimal 1.',
            'satuan_waktu_sewa.required' => 'Satuan waktu sewa wajib diisi untuk properti sewa.',
            'satuan_waktu_sewa.in' => 'Satuan waktu sewa harus Bulan atau Tahun.',
        ];
    }

    private function isSewaListing(): bool
    {
        $sewaId = JenisListing::query()->where('slug', 'sewa')->value('id');

        return $sewaId && (int) $this->input('jenis_listing_id') === (int) $sewaId;
    }

    private function requiresBuildingFields(): bool
    {
        return $this->filled('jenis_objek_id') && ! $this->isObjectWithoutBuildingFields();
    }

    private function isObjectWithoutBuildingFields(): bool
    {
        $ids = JenisObjek::query()
            ->whereIn('slug', ['tanah', 'sawah', 'tanah_kebun'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return in_array((int) $this->input('jenis_objek_id'), $ids, true);
    }

    private function normalizePhoneNumberId(mixed $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1, 15);
        } elseif (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2, 15);
        } else {
            $digits = substr($digits, 0, 15);
        }

        if ($digits === '') {
            return null;
        }

        $groups = [substr($digits, 0, 3)];

        for ($index = 3; $index < strlen($digits); $index += 4) {
            $groups[] = substr($digits, $index, 4);
        }

        return trim(implode(' ', array_filter($groups)));
    }
}
