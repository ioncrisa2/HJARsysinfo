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
            'district_id' => ['nullable', 'string'],
            'peruntukan' => ['nullable', 'string'],
            'jenis_objek' => ['nullable', 'string'],
            'min_harga' => ['nullable', 'numeric', 'min:0'],
            'max_harga' => ['nullable', 'numeric', 'min:0', 'gt:min_harga'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'range_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_harga.gt' => 'Harga maksimal harus lebih besar dari harga minimal',
            'min_harga.min' => 'Harga minimal tidak boleh negatif',
            'max_harga.min' => 'Harga maksimal tidak boleh negatif',
            'range_km.min' => 'Range minimal adalah 0.1 km',
            'range_km.max' => 'Range maksimal adalah 100 km',
        ];
    }
}
