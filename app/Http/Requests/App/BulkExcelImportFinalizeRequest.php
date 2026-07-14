<?php

namespace App\Http\Requests\App;

use App\Models\BulkExcelImportBatch;
use Illuminate\Foundation\Http\FormRequest;

class BulkExcelImportFinalizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof BulkExcelImportBatch
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
