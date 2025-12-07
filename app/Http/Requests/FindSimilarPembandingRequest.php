<?php

namespace App\Http\Requests;

use App\Enums\Peruntukan;
use App\Enums\DokumenTanah;
use App\Enums\PosisiTanah;
use App\Enums\KondisiTanah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FindSimilarPembandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'district_id' => ['required', 'string'],
            'peruntukan' => ['required', 'string', Rule::enum(Peruntukan::class)],
            'luas_tanah' => ['nullable', 'numeric', 'min:0'],
            'luas_bangunan' => ['nullable', 'numeric', 'min:0'],
            'dokumen_tanah' => ['nullable', 'string', Rule::enum(DokumenTanah::class)],
            'lebar_jalan' => ['nullable', 'numeric', 'min:0'],
            'posisi_tanah' => ['nullable', 'string', Rule::enum(PosisiTanah::class)],
            'kondisi_tanah' => ['nullable', 'string', Rule::enum(KondisiTanah::class)],
            'harga' => ['nullable', 'numeric', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'peruntukan.enum' => 'Peruntukan tidak valid',
            'dokumen_tanah.enum' => 'Dokumen tanah tidak valid',
            'posisi_tanah.enum' => 'Posisi tanah tidak valid',
            'kondisi_tanah.enum' => 'Kondisi tanah tidak valid',
        ];
    }
}
