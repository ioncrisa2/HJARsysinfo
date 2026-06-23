<?php

namespace App\Http\Requests\DataContributor;

use Illuminate\Foundation\Http\FormRequest;

class RejectRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ];
    }
}
