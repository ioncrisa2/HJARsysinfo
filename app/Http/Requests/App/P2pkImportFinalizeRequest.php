<?php

namespace App\Http\Requests\App;

use App\Models\P2pkImportBatch;
use Illuminate\Foundation\Http\FormRequest;

class P2pkImportFinalizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof P2pkImportBatch
            && (bool) $this->user()?->can('update', $batch);
    }

    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Centang pernyataan pemeriksaan sebelum memasukkan data.'];
    }
}
