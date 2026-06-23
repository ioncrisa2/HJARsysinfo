<?php

namespace App\Http\Requests\DataContributor;

use App\Models\DataContributorRegistrationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name' => str((string) $this->input('display_name'))->squish()->toString(),
            'phone' => preg_replace('/[^\d+]/', '', (string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'display_name' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $prefix = Str::of((string) $value)
                        ->ascii()
                        ->lower()
                        ->replaceMatches('/[^a-z0-9\s.\-_]+/', '')
                        ->replaceMatches('/[\s.\-_]+/', '.')
                        ->trim('.')
                        ->toString();

                    if ($prefix === '') {
                        $fail('Nama singkat harus berisi huruf atau angka agar bisa dibuat email login.');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('data_contributor_registration_requests', 'phone')
                    ->where(fn ($query) => $query->where('status', DataContributorRegistrationRequest::STATUS_PENDING)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Nama singkat wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.unique' => 'Nomor telepon ini masih memiliki request pending.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }
}
