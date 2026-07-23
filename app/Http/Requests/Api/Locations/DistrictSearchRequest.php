<?php

namespace App\Http\Requests\Api\Locations;

use Illuminate\Foundation\Http\FormRequest;

class DistrictSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID kabupaten/kota induk.
             *
             * @example "3273"
             */
            'regency_id' => ['nullable', 'string', 'max:20'],
            /**
             * Potongan nama kecamatan.
             *
             * @example "Coblong"
             */
            'q' => ['nullable', 'string', 'max:100'],
            /**
             * Maksimal jumlah hasil (1-200).
             *
             * @default 50
             *
             * @example 25
             */
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
