<?php

namespace App\Http\Requests\Api\Locations;

use Illuminate\Foundation\Http\FormRequest;

class RegencySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID provinsi induk.
             *
             * @example "32"
             */
            'province_id' => ['nullable', 'string', 'max:20'],
            /**
             * Potongan nama kabupaten/kota.
             *
             * @example "Bandung"
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
