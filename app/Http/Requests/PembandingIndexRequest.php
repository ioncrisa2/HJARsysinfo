<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PembandingIndexRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:255'],
            'province_id' => ['nullable', 'string'],
            'regency_id' => ['nullable', 'string'],
            'district_id' => ['nullable', 'string'],
            'village_id' => ['nullable', 'string'],
            'jenis_listing_id' => ['nullable', 'integer'],
            'jenis_objek_id' => ['nullable', 'integer'],
            'created_by' => ['nullable', 'integer'],
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'peruntukan' => ['nullable', 'string'],
            'jenis_objek' => ['nullable', 'string'],
            'min_harga' => ['nullable', 'numeric', 'min:0'],
            'max_harga' => ['nullable', 'numeric', 'min:0', 'gte:min_harga'],
            'min_luas_tanah' => ['nullable', 'numeric', 'min:0'],
            'max_luas_tanah' => ['nullable', 'numeric', 'min:0', 'gte:min_luas_tanah'],
            'sort' => ['nullable', 'string', 'in:tanggal_data,harga,luas_tanah,luas_bangunan,created_at,id'],
            'direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'range_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_harga.min' => 'Harga minimal tidak boleh negatif',
            'max_harga.min' => 'Harga maksimal tidak boleh negatif',
            'range_km.min' => 'Range minimal adalah 0.1 km',
            'range_km.max' => 'Range maksimal adalah 100 km',
        ];
    }
}
