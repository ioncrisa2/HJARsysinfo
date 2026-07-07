<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class P2pkImportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('bulk_import_data::pembanding');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('file');
                if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xlsm'], true)) {
                    $validator->errors()->add('file', 'Pilih file Excel berformat .xlsx atau .xlsm.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file Excel yang akan diperiksa.',
            'file.file' => 'File yang dipilih tidak dapat dibaca.',
            'file.max' => 'Ukuran file Excel maksimal 10 MB.',
        ];
    }
}
