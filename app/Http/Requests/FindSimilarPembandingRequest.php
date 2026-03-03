<?php

namespace App\Http\Requests;

use App\Models\DokumenTanah;
use App\Models\KondisiTanah;
use App\Models\Peruntukan;
use App\Models\PosisiTanah;
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
            'peruntukan' => [
                'required',
                'string',
                Rule::exists((new Peruntukan())->getTable(), 'slug')
                    ->where('is_active', true),
            ],
            'luas_tanah' => ['nullable', 'numeric', 'min:0'],
            'luas_bangunan' => ['nullable', 'numeric', 'min:0'],
            'dokumen_tanah' => [
                'nullable',
                'string',
                Rule::exists((new DokumenTanah())->getTable(), 'slug')
                    ->where('is_active', true),
            ],
            'lebar_jalan' => ['nullable', 'numeric', 'min:0'],
            'posisi_tanah' => [
                'nullable',
                'string',
                Rule::exists((new PosisiTanah())->getTable(), 'slug')
                    ->where('is_active', true),
            ],
            'kondisi_tanah' => [
                'nullable',
                'string',
                Rule::exists((new KondisiTanah())->getTable(), 'slug')
                    ->where('is_active', true),
            ],
            'harga' => ['nullable', 'numeric', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'range_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'peruntukan.exists' => 'Peruntukan tidak valid',
            'dokumen_tanah.exists' => 'Dokumen tanah tidak valid',
            'posisi_tanah.exists' => 'Posisi tanah tidak valid',
            'kondisi_tanah.exists' => 'Kondisi tanah tidak valid',
            'range_km.min' => 'Range minimal adalah 0.1 km',
            'range_km.max' => 'Range maksimal adalah 100 km',
        ];
    }
}
