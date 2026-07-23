<?php

namespace App\Http\Requests\Api\Locations;

use Illuminate\Foundation\Http\FormRequest;

class ProvinceSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Potongan nama provinsi.
             *
             * @example "Jawa"
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
